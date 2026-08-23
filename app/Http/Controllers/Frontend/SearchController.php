<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Models\{AiModel,AiTest,Article,Category,Company,NewsItem,SavedSearch,SearchEvent,Tool};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class SearchController extends Controller {
 public function index(Request $request){
  $query=trim((string)$request->query('q','')); $type=(string)$request->query('type','all');
  $allowed=['all','tools','models','news','companies','articles','tests']; if(!in_array($type,$allowed,true))$type='all';
  $counts=array_fill_keys(array_slice($allowed,1),0); $tools=$models=$news=$companies=$articles=$tests=collect();
  if($query!==''){
   $tokens=collect(preg_split('/\s+/u',$query))->filter()->take(6);
   $toolQ=Tool::with(['company','category'])->where('status','published')->where(fn($q)=>$this->tokens($q,$tokens,['name','short_description','description']));
   $modelQ=AiModel::with(['company','tool'])->whereIn('status',['active','preview'])->where(fn($q)=>$this->tokens($q,$tokens,['name','version','capability_notes']));
   $newsQ=NewsItem::with('company')->where('status','published')->where(fn($q)=>$q->whereNull('duplicate_status')->orWhere('duplicate_status','!=','duplicate'))->where(fn($q)=>$this->tokens($q,$tokens,['headline','summary','ai_summary','source','category','ai_topic']));
   $companyQ=Company::withCount(['tools'=>fn($q)=>$q->where('status','published'),'models'=>fn($q)=>$q->whereIn('status',['active','preview'])])->where('status','active')->where(fn($q)=>$this->tokens($q,$tokens,['name','description']));
   $articleQ=Article::with(['author','company','categoryTerm'])->where('status','published')->where(fn($q)=>$this->tokens($q,$tokens,['title','summary','content','category']));
   $testQ=AiTest::with(['feature','useCase'])->withCount(['completedResults as results_count'])->published()->where(fn($q)=>$this->tokens($q,$tokens,['name','short_description','prompt','criteria','category']));
   $qs=['tools'=>$toolQ,'models'=>$modelQ,'news'=>$newsQ,'companies'=>$companyQ,'articles'=>$articleQ,'tests'=>$testQ];
   foreach($qs as $key=>$q)$counts[$key]=(clone $q)->count();
   if(in_array($type,['all','tools']))$tools=$toolQ->orderByDesc('rating')->orderByDesc('popularity')->take($type==='tools'?30:6)->get();
   if(in_array($type,['all','models']))$models=$modelQ->orderByDesc('benchmark_score')->orderByDesc('release_date')->take($type==='models'?30:6)->get();
   if(in_array($type,['all','news']))$news=$newsQ->orderByDesc('published_at')->take($type==='news'?30:6)->get();
   if(in_array($type,['all','companies']))$companies=$companyQ->orderByDesc('tools_count')->take($type==='companies'?30:6)->get();
   if(in_array($type,['all','articles']))$articles=$articleQ->orderByDesc('published_at')->take($type==='articles'?30:6)->get();
   if(in_array($type,['all','tests']))$tests=$testQ->orderByDesc('is_featured')->orderByDesc('published_at')->take($type==='tests'?30:6)->get();
   $sessionFingerprint = hash_hmac(
       'sha256',
       $request->session()->getId(),
       (string) config('app.key')
   );

   SearchEvent::create([
       'user_id'=>$request->user()?->id,
       'query'=>Str::lower($query),
       'type'=>$type,
       'result_count'=>array_sum($counts),
       'session_key'=>$sessionFingerprint,
   ]);
  }
  $popularCategories=Category::product()->active()->withCount(['tools'=>fn($q)=>$q->where('status','published')])->orderByDesc('tools_count')->take(8)->get();
  $trendingTools=Tool::with('company')->where('status','published')->orderByDesc('popularity')->orderByDesc('rating')->take(6)->get();
  $recentSearches=$request->user()? SearchEvent::where('user_id',$request->user()->id)->latest()->pluck('query')->unique()->take(6):collect();
  $savedSearches=$request->user()? SavedSearch::where('user_id',$request->user()->id)->latest()->take(8)->get():collect();
  return view('frontend.search.index',compact('query','type','counts','tools','models','news','companies','articles','tests','popularCategories','trendingTools','recentSearches','savedSearches')+['total'=>array_sum($counts)]);
 }
 public function save(Request $r){$d=$r->validate(['query'=>'required|string|max:180','type'=>'nullable|in:all,tools,models,news,companies,articles,tests']);SavedSearch::firstOrCreate(['user_id'=>$r->user()->id,'query'=>trim($d['query']),'type'=>$d['type']??'all']);return back()->with('status','Search saved.');}
 public function destroySaved(Request $r,SavedSearch $savedSearch){abort_unless($savedSearch->user_id===$r->user()->id,403);$savedSearch->delete();return back()->with('status','Saved search removed.');}
 public function click(Request $r){$d=$r->validate(['query'=>'required|string|max:180','target_type'=>'required|in:tool,model,news,company,article,test','target_id'=>'required|integer|min:1']);SearchEvent::where('user_id',$r->user()?->id)->where('query',Str::lower(trim($d['query'])))->latest()->first()?->update(['clicked'=>true,'clicked_type'=>$d['target_type'],'clicked_id'=>$d['target_id']]);return response()->json(['ok'=>true]);}
 private function tokens($q,$tokens,array $cols){
  foreach($tokens as $token){
   $safeToken=addcslashes((string)$token,'\\%_');
   $q->where(function($x)use($safeToken,$cols){
    foreach($cols as $i=>$col){
     $method=$i?'orWhere':'where';
     $x->{$method}($col,'like','%'.$safeToken.'%');
    }
   });
  }
  return $q;
 }
}