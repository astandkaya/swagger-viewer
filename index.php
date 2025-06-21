<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

/* ---------- autoload / YAML ---------- */
require_once __DIR__.'/vendor/autoload.php';

// $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();
$yaml_path = $_ENV['YAML_PATH'];

function e(string $s):string{ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }


if(!function_exists('yaml_parse_file')) die('php-yaml 拡張が有効ではありません。');
$yamlFile = __DIR__.'/'.$yaml_path ;
if(!is_file($yamlFile)) die($yaml_path .' が見つかりません。');
$apiSpec = yaml_parse_file($yamlFile);

/* ---------- $ref 解決 ---------- */
function resolveRef(string $ref,array $spec):array{
    if(strpos($ref,'#/')!==0) return [];
    $node=$spec; foreach(explode('/',substr($ref,2)) as $p){
        if(!isset($node[$p])) return []; $node=$node[$p];
    } return $node;
}
function schema(array $s,array $spec){ return isset($s['$ref'])?array_merge(resolveRef($s['$ref'],$spec),$s):$s; }

/* ---------- メタ情報 ---------- */
$info        = $apiSpec['info']??[];
$title       = $info['title']   ??'API Documentation';
$version     = $info['version'] ??'';
$description = $info['description']??'';
$paths       = $apiSpec['paths']??[];

/* ---------- グループ化 ---------- */
$groups=[];
foreach($paths as $p=>$m){
    $t=$m[array_key_first($m)]['tags'][0]??explode('/',trim($p,'/'))[1]??'root';
    $groups[$t][$p]=$m;
}
ksort($groups);

/* ---------- util ---------- */
function aid(string $p):string{ return 'ep-'.trim(preg_replace('/[^a-z0-9]+/i','-',$p),'-'); }
function ename(string $p,array $m):string{
    return $m[array_key_first($m)]['summary']??str_replace('/v1/','',$p);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title)?></title>

<!-- Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-..." crossorigin="anonymous">

<!-- Highlight.js（必要な言語のみ） -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/monokai-sublime.min.css" defer>

<style>
/* ---------- 基本色 ---------- */
:root {
  --bg        : #f8f9fa;
  --card      : #fff;
  --text      : #212529;
  --border    : #dee2e6;
  --header-bg : linear-gradient(135deg, #0d6efd 0%, #00c7ff 100%);
  --table-head: #f1f3f5;
  --table-row : #fafafa;
  --code-bg   : #f6f8fa;
}

html, body {
  background: var(--bg);
  color: var(--text);
}

a {
  color: #0d6efd;
}

/* ---------- Header ---------- */
.page-header {
  background: var(--header-bg);
  color: #fff;
  padding: 3rem 1rem 4rem;
  margin-bottom: 2rem;
  text-align: center;
}

/* ---------- Sidebar ---------- */
#side {
  position: sticky;
  top: 20px;
  max-height: calc(100vh - 40px);
  overflow-y: auto;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: .5rem;
}

#side summary {
  cursor: pointer;
  padding: .5rem .75rem;
  font-weight: 600;
  border-bottom: 1px solid var(--border);
}

#side .nav-link {
  padding: .25rem .75rem;
}

#side .nav-link.active {
  font-weight: bold;
  background: rgba(13,110,253,.15);
}

/* ---------- Endpoint card ---------- */
details.ep {
  margin-bottom: 1rem;
  border: 1px solid var(--border);
  border-radius: .5rem;
  background: var(--card);
}

details.ep summary {
  padding: .75rem 1rem;
  font-weight: 600;
  cursor: pointer;
  border-bottom: 1px solid var(--border);
}

details.ep[open] summary {
  background: rgba(13,110,253,.1);
}

details.ep .inner {
  padding: 1rem 1.25rem;
}

/* ---------- Badges ---------- */
.badge-method {
  font-size: .75rem;
}

.badge-GET    { background: #198754; }
.badge-POST   { background: #0d6efd; }
.badge-PUT    { background: #fd7e14; }
.badge-DELETE { background: #dc3545; }
.badge-PATCH  { background: #6f42c1; }

/* ---------- Tables ---------- */
table {
  background: var(--card);
}

thead {
  background: var(--table-head);
}

.table-striped tbody tr:nth-of-type(odd) {
  background: var(--table-row);
}

.table-hover tbody tr:hover {
  background: rgba(0,0,0,.05);
}

/* ---------- Code block ---------- */
pre.code {
  background: var(--code-bg);
  padding: 1rem;
  border-radius: .5rem;
  overflow: auto;
}
</style>
</head>
<body>

<header class="page-header">
<h1 class="h3 mb-0"><?=e($title)?></h1>
<?php if($version):?><p class="mb-1">v<?=e($version)?></p><?php endif;?>
<?php if($description):?><p class="mb-0"><?=nl2br(e($description))?></p><?php endif;?>
</header>

<div class="container-fluid">
<div class="row g-4 px-4">

<!-- ---------- Sidebar ---------- -->
<nav id="side" class="col-lg-3 col-md-4 col-12">
 <div class="p-3">
  <h5 class="mb-3">エンドポイント</h5>
  <?php foreach($groups as $g=>$eps):?>
   <details <?=count($groups)==1?'open':''?>>
    <summary><?=e($g)?></summary>
    <div class="nav flex-column mb-2">
     <?php foreach($eps as $p=>$m):?>
       <a class="nav-link" href="#<?=aid($p)?>"><?=e(ename($p,$m))?></a>
     <?php endforeach;?>
    </div>
   </details>
  <?php endforeach;?>
 </div>
</nav>

<!-- ---------- Main ---------- -->
<main class="col-lg-9 col-md-8 col-12">
  <?php foreach($groups as $g=>$eps):?>
    <h2 class="mb-3"><?=e($g)?></h2>
    <?php foreach($eps as $path=>$methods):
          $id=aid($path);?>
    <details id="<?=$id?>" class="ep">
      <summary><span class="text-muted small me-2"><?=e($path)?></span><?=e(ename($path,$methods))?></summary>
      <div class="inner">
        <?php foreach($methods as $verb=>$op):
              $VU=strtoupper($verb);
              $params=$op['parameters']??[];
              $resp  =$op['responses'] ??[];
        ?>
        <h5 class="mt-3">
          <span class="badge badge-method badge-<?=$VU?> me-2"><?=$VU?></span>
          <?=e($op['summary']??'（要約なし）')?>
        </h5>
        <?php if(!empty($op['description'])):?><p><?=nl2br(e($op['description']))?></p><?php endif;?>

        <?php if($params):?>
        <h6 class="mt-3">パラメータ</h6>
        <table class="table table-sm table-striped table-hover border">
          <thead><tr><th>名前</th><th>In</th><th>型</th><th>説明</th><th>必須</th></tr></thead>
          <tbody>
          <?php foreach($params as $pa):
                $sc=isset($pa['schema'])?schema($pa['schema'],$apiSpec):[];?>
            <tr>
              <td><?=e($pa['name']??'')?></td>
              <td><?=e($pa['in']??'')?></td>
              <td><?=e($sc['type']??'')?></td>
              <td><?=e($pa['description']??'')?></td>
              <td><?=!empty($pa['required'])?'Yes':'No'?></td>
            </tr>
          <?php endforeach;?>
          </tbody>
        </table>
        <?php endif;?>

        <?php if(!empty($op['requestBody'])):
              $rb=$op['requestBody'];?>
        <h6 class="mt-3">リクエストボディ</h6>
        <p><strong>必須:</strong> <?=!empty($rb['required'])?'Yes':'No'?></p>
        <?php foreach($rb['content'] as $mt=>$dt):
              $sc=isset($dt['schema'])?schema($dt['schema'],$apiSpec):[];?>
         <p class="fw-semibold mb-1">メディアタイプ: <?=e($mt)?></p>
         <?php if($sc && !empty($sc['properties'])):?>
         <table class="table table-sm table-bordered">
          <thead class="table-light"><tr><th>プロパティ</th><th>型</th><th>説明</th></tr></thead><tbody>
           <?php foreach($sc['properties'] as $pn=>$pd):?>
            <tr><td><?=e($pn)?></td><td><?=e($pd['type']??'')?></td><td><?=e($pd['description']??'')?></td></tr>
           <?php endforeach;?>
          </tbody>
         </table>
         <?php
           $ex=[];foreach($sc['properties'] as $pn=>$pd){if(isset($pd['example'])) $ex[$pn]=$pd['example'];}
           if($ex):?>
           <pre class="code"><code class="language-json"><?=json_encode($ex,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)?></code></pre>
         <?php endif; endif;?>
        <?php endforeach; endif;?>

        <?php if($resp):?>
        <h6 class="mt-3">レスポンス</h6>
        <table class="table table-sm table-striped table-hover border">
          <thead><tr><th>ステータス</th><th>説明</th></tr></thead><tbody>
          <?php foreach($resp as $c=>$r):?>
           <tr><td><?=e((string)$c)?></td><td><?=e($r['description']??'')?></td></tr>
          <?php endforeach;?>
          </tbody>
        </table>
        <?php endif;?>
        <hr>
        <?php endforeach;?>
      </div>
    </details>
    <?php endforeach;?>
  <?php endforeach;?>
</main>

</div></div><!-- /row /container -->

<!-- JS（Bootstrap + カスタム） -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>
<script>
/* ---------- Highlight.js 初期化 ---------- */
document.addEventListener('DOMContentLoaded',()=>{ if(window.hljs){ hljs.highlightAll(); }});

/* ---------- ハッシュに応じて <details> を開く ---------- */
function openDetailsFromHash(){
  const id=location.hash.slice(1);
  if(!id) return;
  const el=document.getElementById(id);
  if(el && el.tagName==='DETAILS') el.open=true;
}
window.addEventListener('load',openDetailsFromHash);
window.addEventListener('hashchange',openDetailsFromHash);
</script>
</body>
</html>