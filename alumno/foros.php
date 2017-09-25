<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web = new ForoControllers;
$web->iniClases('usuario', "index foros");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);
$web->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c

$libros      = $web->getAllLibros();
$totalLibros = count($libros);
$per_page    = 3;
$pages       = ceil($totalLibros / $per_page);
$tmp         = array();

for ($i = 0; $i < $totalLibros; $i++) {
  $sinopsis            = $libros[$i]['sinopsis'];
  $libros[$i]['intro'] = (strlen($sinopsis) > 255) ? substr($sinopsis, 0, 254) . "[..]" : $sinopsis;

  $portada               = explode("/", $web->checkPortada($libros[$i]['cvelibro']));
  $numElements           = count($portada);
  $libros[$i]['portada'] = ($numElements > 1) ? $portada[($numElements - 1)] : $portada[0];

  $web->smarty->assign('libro', $libros[$i]);
  $web->smarty->assign('route', "../");
  $tmp[$i] = $web->smarty->fetch('../foro.component.html');
}

$libros = array();
$cl     = 0;
for ($i = 0; $i < $pages; $i++) {
  $html = "";
  for ($j = 0; $j < $per_page; $j++) {
    if ($cl < $totalLibros) {
      $html .= $tmp[$cl];
      $cl++;
    }
  }
  $libros[$i] = $html;
}

$web->smarty->assign('fin', $pages - 1);
$web->smarty->assign('pages', $pages);
$web->smarty->assign('per_page', $per_page);
$web->smarty->assign('libros', $libros);
$web->smarty->assign('foros', true);
$web->smarty->display('foro/foros.html');

/************************************************************************************
 * FUNCIONES
 ************************************************************************************/
