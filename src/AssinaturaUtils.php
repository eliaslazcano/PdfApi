<?php

namespace App;

use DateTime;

class AssinaturaUtils
{
  /**
   * As informações das assinaturas costumam ficar juntas em uma unica string. Esta função separa cada uma.
   * @param string $input - Conteúdo puro das assinaturas numa única string.
   * @return string[]  - Cada assinatura é um item do array, contendo suas informações ainda em string.
   */
  private static function separarAssinaturas(string $input): array
  {
    $pattern = '/Signature\s+#\d+:(.*?)(?=(Signature\s+#\d+:|$))/s'; // Expressão regular para capturar todas as assinaturas
    $assinaturas = []; // Array para armazenar as assinaturas
    preg_match_all($pattern, $input, $matches); // Usando preg_match_all para capturar todas as assinaturas

    if (!empty($matches[1])) { // Verifica se encontramos alguma assinatura
      foreach ($matches[1] as $ass) { // Para cada assinatura encontrada, limpamos e adicionamos ao array
        $assinaturas[] = trim($ass); // Limpa espaços extras e quebras de linha
      }
    }

    return $assinaturas;
  }

  /**
   * Informando todos dados de uma assinatura (em string) esta função consegue extrair um dado específico.
   * @param string $assinatura - Todos os dados da assiantura.
   * @param string $informacao - Nome da informação que deseja extrair.
   * @return string|null - null se a informação não for encontrada.
   */
  private static function extrairInformacao(string $assinatura, string $informacao): ?string
  {
    $pattern = "/$informacao:\s*(.*)/";
    preg_match($pattern, $assinatura, $matches); // Variável para armazenar o resultado
    return isset($matches[1]) ? trim($matches[1]) : null;
  }

  /**
   * Converte as informações de uma assinatura em array associativo.
   * @param string $assinatura
   * @return array
   */
  private static function hidratarAssinatura(string $assinatura): array
  {
    $data = self::extrairInformacao($assinatura, 'Signing Time');
    if ($data) $data = DateTime::createFromFormat('M d Y H:i:s', $data);
    return [
      'campo' => self::extrairInformacao($assinatura, 'Signature Field Name'),
      'assinante' => self::extrairInformacao($assinatura, 'Signer Certificate Common Name'),
      'distinto' => self::extrairInformacao($assinatura, 'Signer full Distinguished Name'),
      'data' => $data ? $data->format('Y-m-d H:i:s') : null,
      'algoritmo' => self::extrairInformacao($assinatura, 'Signing Hash Algorithm'),
      'tipo' => self::extrairInformacao($assinatura, 'Signature Type'),
      'validacao' => self::extrairInformacao($assinatura, 'Signature Validation') === 'Signature is Valid.',
    ];
  }

  /**
   * Converte todas as informações das assinaturas em array associativo.
   * @param string $input - Conteúdo puro das assinaturas numa única string.
   * @return array
   */
  public static function parseAssinaturas(string $input): array
  {
    $assinaturas = self::separarAssinaturas($input);
    return array_map(fn($i) => self::hidratarAssinatura($i), $assinaturas);
  }

  /**
   * Retorna um array contendo a informação de todas as assinaturas do documento PDF.
   * @param string $path - Caminho até o arquivo PDF.
   * @return array - Cada item é uma assinatura, dentro dela há um array associativo com suas informações.
   */
  public static function getAssinaturas(string $path): array
  {
    $saida = shell_exec("pdfsig $path");
    return $saida ? self::parseAssinaturas($saida) : [];
  }
}