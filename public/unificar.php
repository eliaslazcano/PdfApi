<?php
/**
 * Unifica os documentos recebidos em um só.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\PdfUtils;
use App\Utils;
use Eliaslazcano\Helpers\HttpHelper;

HttpHelper::validarPost();

if (empty($_FILES)) HttpHelper::erroJson(400, 'Nenhum arquivo enviado');

$arquivos = Utils::getAllUploadedFiles();

try {
  $pathArquivosOriginais = [];
  foreach ($arquivos as $index => $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) continue;
    $extensao = $file['name'] ? pathinfo($file['name'], PATHINFO_EXTENSION) : '';
    if ($file['type'] === 'application/pdf' || strtolower($extensao) === 'pdf') $pathArquivosOriginais[] = $file['tmp_name'];
  }

  $pathTemporario = Utils::getPathTemporario('.pdf');
  PdfUtils::unificarQpdf($pathArquivosOriginais, $pathTemporario);
  if (!file_exists($pathTemporario)) throw new Exception('O servidor não pôde gravar o arquivo resultante.');

  header('Content-Type: application/pdf');
  header("Content-Disposition: attachment; filename=$pathTemporario");
  header('Content-Length: ' . filesize($pathTemporario));
  readfile($pathTemporario);
  unlink($pathTemporario);
} catch (Exception $e) {
  HttpHelper::erroJson(500, $e->getMessage());
}