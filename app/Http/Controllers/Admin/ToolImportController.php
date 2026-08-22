<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tool;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
use App\Services\Taxonomy\TaxonomyNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ToolImportController extends Controller
{
    public function template()
    {
        $path = storage_path('app/import-templates/ai-hub-tools-import.csv');
        abort_unless(File::exists($path),404,'Tool import template not found.');
        return response()->download($path,'ai-hub-tools-import.csv');
    }

    public function preview(Request $request, SpreadsheetReader $reader, ImportPreviewStore $store, TaxonomyNormalizer $taxonomy)
    {
        $request->validate(['file'=>['required','file','mimes:csv,txt,xlsx','max:10240']]);
        try { $rows=$reader->read($request->file('file')); } catch(Throwable $e){ return back()->withErrors(['file'=>$e->getMessage()]); }
        if(count($rows)>3000) return back()->withErrors(['file'=>'A single tool import is limited to 3,000 rows.']);

        $companies=Company::get(['id','name'])->keyBy(fn($c)=>mb_strtolower(trim($c->name)));
        $seen=[];$preview=[];
        foreach($rows as $row){
            $n=$this->normalize($row);$errors=[];
            if($n['name']==='')$errors[]='Tool name is required.';
            if($n['website']!==''&&!filter_var($n['website'],FILTER_VALIDATE_URL))$errors[]='Website is not a valid URL.';
            if($n['launch_date']!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$n['launch_date']))$errors[]='Launch date must use YYYY-MM-DD.';
            if(!in_array($n['status'],['draft','published','archived'],true))$errors[]='Status must be draft, published or archived.';

            $company=$n['company']!==''?$companies->get(mb_strtolower($n['company'])):null;
            if($n['company']!==''&&!$company)$errors[]='Missing company in database: '.$n['company'];

            $category=$taxonomy->productCategoryByName($n['category']);
            if($n['category']!==''&&!$category)$errors[]='Unknown product category: '.$n['category'].'. Use a Taxonomy v2 category.';
            $subcategory=$taxonomy->subcategoryByName($n['subcategory'],$category?->id);
            if (!$subcategory && $n['subcategory']==='' && $category) $subcategory=$taxonomy->defaultSubcategoryForCategory($n['category'],$category->id);
            if (!$category && $subcategory) $category = $subcategory->category;
            if($n['subcategory']!==''&&!$subcategory)$errors[]='Unknown subcategory for selected category: '.$n['subcategory'];

            $n['capabilities']=$taxonomy->canonicalFeatureNames($n['capabilities']);
            foreach($taxonomy->unknownFeatureNames($n['capabilities']) as $unknown)$errors[]='Unknown Taxonomy v2 capability: '.$unknown;

            $key=mb_strtolower(($n['company']?:'independent').'|'.$n['name']);
            if(isset($seen[$key]))$errors[]='Duplicate tool inside this file.';$seen[$key]=true;
            $existing=Tool::query()->where('name',$n['name'])->when($company,fn($q)=>$q->where('company_id',$company->id))->first();
            $preview[]=$n+[
                'company_id'=>$company?->id,'company_match'=>$company?->name,'category_id'=>$category?->id,'category_match'=>$category?->name,
                'subcategory_id'=>$subcategory?->id,'subcategory_match'=>$subcategory?->name,
                'default_tag_ids'=>$taxonomy->defaultTagIdsForCategory($n['category']),
                'default_use_case_ids'=>$taxonomy->inferredUseCaseIds($n['capabilities'],$n['category']),
                'existing_id'=>$existing?->id,
                'errors'=>array_values(array_unique($errors)),'state'=>$errors?'invalid':($existing?'existing':'ready'),
            ];
        }
        $token=$store->put('tools',$request->user()->id,$preview);$stats=$this->stats($preview);
        return view('data-import.tools-preview',compact('preview','stats','token'));
    }

    public function commit(Request $request, ImportPreviewStore $store, TaxonomyNormalizer $taxonomy)
    {
        $data=$request->validate(['token'=>['required','string','size:40'],'existing_action'=>['required',Rule::in(['skip','update'])]]);
        $payload=$store->get($data['token'],$request->user()->id,'tools');
        $created=$updated=$skipped=$invalid=0;
        DB::transaction(function()use($payload,$data,$taxonomy,&$created,&$updated,&$skipped,&$invalid){
            foreach($payload['rows']??[] as $row){
                if(($row['state']??'')==='invalid'||!empty($row['errors'])){$invalid++;continue;}
                $values=[
                    'company_id'=>$row['company_id']?:null,'category_id'=>$row['category_id']?:null,'subcategory_id'=>$row['subcategory_id']?:null,
                    'subcategory'=>$row['subcategory_match']?:null,'name'=>$row['name'],'website'=>$row['website']?:null,'launch_date'=>$row['launch_date']?:null,
                    'short_description'=>$row['short_description']?:null,'description'=>$row['description']?:null,'pricing_models'=>$row['pricing_models'],
                    'platforms'=>$row['platforms'],'capabilities'=>$row['capabilities'],'status'=>$row['status'],'seo_title'=>$row['seo_title']?:null,
                    'meta_description'=>$row['meta_description']?:null,'published_at'=>$row['status']==='published'?now():null,
                ];
                $existing=Tool::query()->where('name',$row['name'])->when($row['company_id'],fn($q,$id)=>$q->where('company_id',$id))->first();
                if($existing){
                    if($data['existing_action']==='skip'){$skipped++;continue;}
                    $values['slug']=$existing->slug?:$this->uniqueToolSlug($row['name'],$existing->id);
                    if($values['status']==='published')$values['published_at']=$existing->published_at?:now();
                    $existing->update($values);$tool=$existing;$updated++;
                }else{
                    $values['slug']=$this->uniqueToolSlug($row['name']);$tool=Tool::create($values);$created++;
                }
                $tool->featureTerms()->sync($taxonomy->featureIds($row['capabilities']));
                $tool->useCaseTerms()->sync($row['default_use_case_ids'] ?? $taxonomy->inferredUseCaseIds($row['capabilities'],$row['category'] ?? null));
                if (!empty($row['default_tag_ids'])) {
                    $tool->tagTerms()->syncWithoutDetaching($row['default_tag_ids']);
                    $tool->updateQuietly(['tags'=>$tool->tagTerms()->orderBy('name')->pluck('name')->all()]);
                }
            }
        });
        $store->forget($data['token']);
        return redirect()->route('admin.tools.index')->with('status',"Tool import complete: {$created} created, {$updated} updated, {$skipped} existing skipped, {$invalid} invalid skipped. Taxonomy v2 relations synchronized.");
    }

    private function normalize(array $r):array
    {
        $split=fn($v)=>array_values(array_unique(array_filter(array_map('trim',preg_split('/[|;,]+/',(string)$v)?:[]))));
        return ['row_number'=>(int)($r['row_number']??0),'company'=>trim((string)($r['company']??'')),'name'=>trim((string)($r['name']??'')),
            'website'=>trim((string)($r['website']??'')),'category'=>trim((string)($r['category']??'')),'subcategory'=>trim((string)($r['subcategory']??'')),
            'launch_date'=>trim((string)($r['launch_date']??'')),'short_description'=>trim((string)($r['short_description']??'')),
            'description'=>trim((string)($r['description']??'')),'pricing_models'=>$split($r['pricing_models']??''),'platforms'=>$split($r['platforms']??''),
            'capabilities'=>$split($r['capabilities']??''),'status'=>strtolower(trim((string)($r['status']??'published')))?:'published',
            'seo_title'=>trim((string)($r['seo_title']??'')),'meta_description'=>trim((string)($r['meta_description']??'')),'source_url'=>trim((string)($r['source_url']??''))];
    }
    private function stats(array $rows):array{return ['total'=>count($rows),'ready'=>collect($rows)->where('state','ready')->count(),'existing'=>collect($rows)->where('state','existing')->count(),'invalid'=>collect($rows)->where('state','invalid')->count()];}
    private function uniqueToolSlug(string $name,?int $ignore=null):string{$base=Str::slug($name)?:'ai-tool';$slug=$base;$i=2;while(Tool::where('slug',$slug)->when($ignore,fn($q)=>$q->where('id','!=',$ignore))->exists())$slug=$base.'-'.$i++;return $slug;}
}
