<?php
/**
 * Analisa todos os arquivos enviados, retornando um array que informando as assinaturas contidas em cada um deles.
 * Exemplo de retorno:
[
  {
    "arquivo": "recibo_msinova_assinado.pdf",
    "assinaturas": [
      {
        "campo": "Signature1",
        "assinante": "ELIAS LAZCANO CASTRO NETO",
        "distinto": "CN=ELIAS LAZCANO CASTRO NETO",
        "data": "2023-01-13 18:04:43",
        "algoritmo": "SHA-256",
        "tipo": "adbe.pkcs7.detached",
        "validacao": true
      }
    ]
  },
  {
    "arquivo": "Recibo.docx",
    "assinaturas": []
  }
]
 */

use App\Utils;
use App\AssinaturaUtils;
use Eliaslazcano\Helpers\HttpHelper;

HttpHelper::validarPost();

if (empty($_FILES)) HttpHelper::erroJson(400, 'Nenhum arquivo enviado');

$arquivos = Utils::getAllUploadedFiles();

//Consulta a assinatura de cada um
$arquivosConsultados = array_map(function ($i) {
  $assinaturas = [];
  $extensao = $i['name'] ? pathinfo($i['name'], PATHINFO_EXTENSION) : '';
  if (strtolower($extensao) === 'pdf' || $i['type'] === 'application/pdf') {
    $assinaturas = AssinaturaUtils::getAssinaturas($i['tmp_name']);
  }
  return [
    'arquivo' => $i['name'],
    'assinaturas' => $assinaturas
  ];
}, $arquivos);

HttpHelper::emitirJson($arquivosConsultados);
