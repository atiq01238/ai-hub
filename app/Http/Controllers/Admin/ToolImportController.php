<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Tool;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
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
        abort_unless(File::exists($path), 404, 'Tool import template not found.');
        return response()->download($path, 'ai-hub-tools-import.csv');
    }

    public function preview(Request $request, SpreadsheetReader $reader, ImportPreviewStore $store)
    {
        $request->validate(['file' => ['required','file','mimes:csv,txt,xlsx','max:10240']]);
        try { $rows = $reader->read($request->file('file')); }
        catch (Throwable $e) { return back()->withErrors(['file' => $e->getMessage()]); }
        if (count($rows) > 3000) return back()->withErrors(['file' => 'A single tool import is limited to 3,000 rows.']);

        $companies = Company::get(['id','name'])->keyBy(fn($c) => mb_strtolower(trim($c->name)));
        $categories = Category::get(['id','name'])->keyBy(fn($c) => mb_strtolower(trim($c->name)));
        $seen = []; $preview = [];
        foreach ($rows as $row) {
            $n = $this->normalize($row); $errors = [];
            if ($n['name']==='') $errors[]='Tool name is required.';
            if ($n['website']!=='' && !filter_var($n['website'], FILTER_VALIDATE_URL)) $errors[]='Website is not a valid URL.';
            if ($n['launch_date']!=='' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$n['launch_date'])) $errors[]='Launch date must use YYYY-MM-DD.';
            if (!in_array($n['status'],['draft','published','archived'],true)) $errors[]='Status must be draft, published or archived.';
            $company = $n['company']!=='' ? $companies->get(mb_strtolower($n['company'])) : null;
            if ($n['company']!=='' && !$company) $errors[]='Missing company in database: '.$n['company'];
            $category = $n['category']!=='' ? $categories->get(mb_strtolower($n['category'])) : null;
            $key=mb_strtolower(($n['company'] ?: 'independent').'|'.$n['name']);
            if(isset($seen[$key])) $errors[]='Duplicate tool inside this file.'; $seen[$key]=true;
            $existing=Tool::query()->where('name',$n['name'])->when($company,fn($q)=>$q->where('company_id',$company->id))->first();
            $preview[]=$n+[
                'company_id'=>$company?->id,'company_match'=>$company?->name,'category_id'=>$category?->id,
                'category_match'=>$category?->name,'category_will_create'=>($n['category']!=='' && !$category),
                'existing_id'=>$existing?->id,'errors'=>array_values(array_unique($errors)),
                'state'=>$errors?'invalid':($existing?'existing':'ready'),
            ];
        }
        $token=$store->put('tools',$request->user()->id,$preview);
        $stats=$this->stats($preview);
        return view('data-import.tools-preview',compact('preview','stats','token'));
    }

    public function commit(Request $request, ImportPreviewStore $store)
    {
        $data=$request->validate(['token'=>['required','string','size:40'],'existing_action'=>['required',Rule::in(['skip','update'])]]);
        $payload=$store->get($data['token'],$request->user()->id,'tools');
        $created=$updated=$skipped=$invalid=0;
        DB::transaction(function() use($payload,$data,&$created,&$updated,&$skipped,&$invalid){
            foreach($payload['rows']??[] as $row){
                if(($row['state']??'')==='invalid'||!empty($row['errors'])){$invalid++;continue;}
                $categoryId=$row['category_id']??null;
                if(!$categoryId && !empty($row['category'])){
                    $category=Category::firstOrCreate(['name'=>$row['category']],['slug'=>$this->uniqueCategorySlug($row['category'])]);
                    $categoryId=$category->id;
                }
                $values=[
                    'company_id'=>$row['company_id']?:null,'category_id'=>$categoryId,'subcategory'=>$row['subcategory']?:null,
                    'name'=>$row['name'],'website'=>$row['website']?:null,'launch_date'=>$row['launch_date']?:null,
                    'short_description'=>$row['short_description']?:null,'description'=>$row['description']?:null,
                    'pricing_models'=>$row['pricing_models'],'platforms'=>$row['platforms'],'capabilities'=>$row['capabilities'],
                    'status'=>$row['status'],'seo_title'=>$row['seo_title']?:null,'meta_description'=>$row['meta_description']?:null,
                    'published_at'=>$row['status']==='published'?now():null,
                ];
                $existing=Tool::query()->where('name',$row['name'])->when($row['company_id'],fn($q,$id)=>$q->where('company_id',$id))->first();
                if($existing){
                    if($data['existing_action']==='skip'){$skipped++;continue;}
                    $values['slug']=$existing->slug ?: $this->uniqueToolSlug($row['name'],$existing->id);
                    if($values['status']==='published') $values['published_at']=$existing->published_at ?: now();
                    $existing->update($values);$updated++;
                } else {
                    $values['slug']=$this->uniqueToolSlug($row['name']);Tool::create($values);$created++;
                }
            }
        });
        $store->forget($data['token']);
        return redirect()->route('admin.tools.index')->with('status',"Tool import complete: {$created} created, {$updated} updated, {$skipped} existing skipped, {$invalid} invalid skipped.");
    }

    private function normalize(array $r): array
    {
        $split=fn($v)=>array_values(array_unique(array_filter(array_map('trim',preg_split('/[|;,]+/',(string)$v)?:[]))));
        return [
            'row_number'=>(int)($r['row_number']??0),'company'=>trim((string)($r['company']??'')),'name'=>trim((string)($r['name']??'')),
            'website'=>trim((string)($r['website']??'')),'category'=>trim((string)($r['category']??'')),'subcategory'=>trim((string)($r['subcategory']??'')),
            'launch_date'=>trim((string)($r['launch_date']??'')),'short_description'=>trim((string)($r['short_description']??'')),
            'description'=>trim((string)($r['description']??'')),'pricing_models'=>$split($r['pricing_models']??''),'platforms'=>$split($r['platforms']??''),
            'capabilities'=>$split($r['capabilities']??''),'status'=>strtolower(trim((string)($r['status']??'published')))?:'published',
            'seo_title'=>trim((string)($r['seo_title']??'')),'meta_description'=>trim((string)($r['meta_description']??'')),'source_url'=>trim((string)($r['source_url']??'')),
        ];
    }
    private function stats(array $rows): array{return ['total'=>count($rows),'ready'=>collect($rows)->where('state','ready')->count(),'existing'=>collect($rows)->where('state','existing')->count(),'invalid'=>collect($rows)->where('state','invalid')->count()];}
    private function uniqueToolSlug(string $name,?int $ignore=null):string{$base=Str::slug($name)?:'ai-tool';$slug=$base;$i=2;while(Tool::where('slug',$slug)->when($ignore,fn($q)=>$q->where('id','!=',$ignore))->exists())$slug=$base.'-'.$i++;return $slug;}
    private function uniqueCategorySlug(string $name):string{$base=Str::slug($name)?:'category';$slug=$base;$i=2;while(Category::where('slug',$slug)->exists())$slug=$base.'-'.$i++;return $slug;}
}
