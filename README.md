# PDF API

Endpoints da API:

| Endpoint     | Descricao                                                                   |
|--------------|-----------------------------------------------------------------------------|
| /comprimir   | Comprime todos os arquivos enivados, se forem varios, retorna em zip        |
| /assinaturas | Retorna em array a informacao das assinaturas de todos os arquivos enviados |
| /unificar    | Unifica todos os arquivos enviados em um unico PDF                          |

## Construir imagem

```bash
docker build --pull --rm -t eliaslazcano/pdfapi:2.0 .
```

## Tecnologias utilizadas

### QPDF

Consegue rápidamente unir documento e também comprimir, sem perder assinaturas, mas a compressão é muito fraca.

#### Instalação

```bash
apt-get install qpdf
```

#### Compressão

```bash
qpdf --compress-streams=y --object-streams=generate documento1.pdf qpdf_compressed1.pdf
```

#### Unificar

```bash
qpdf --empty --pages documento0.pdf documento1.pdf documento2.pdf documento3.pdf -- qpdf_merged.pdf
```

### Ghostscript

Comprime o documento PDF reduzindo o DPI das fotos, mas a custo de perder a assinatura digital.

#### Instalação

```bash
apt install ghostscript poppler-utils
```
#### Compressão

```bash
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -dAutoRotatePages=/None -sOutputFile=ghostscript_compressed2.pdf documento2.pdf
```

### Poppler Utils

Consegue verificar se o documento possui alguma assinatura digital

#### Instalação

```bash
apt-get install poppler-utils
```

#### Verificação

```bash
pdfsig documento3.pdf
```

Quando existe assinaturas, o texto de saída no terminal irá superar 1 linha, exemplo:

```text
Digital Signature Info of: documento3.pdf
Signature #1:
  - Signature Field Name: Signature1
  - Signer Certificate Common Name: ELIAS LAZCANO CASTRO NETO
  - Signer full Distinguished Name: CN=ELIAS LAZCANO CASTRO NETO
  - Signing Time: Feb 02 2024 17:08:14
  - Signing Hash Algorithm: SHA-256
  - Signature Type: adbe.pkcs7.detached
  - Signed Ranges: [0 - 220006], [238952 - 249494]
  - Not total document signed
  - Signature Validation: Signature is Valid.
  - Certificate Validation: Certificate issuer is unknown.
Signature #2:
  - Signature Field Name: Signature2
  - Signer Certificate Common Name: RODRIGO MARQUES TEIXEIRA
  - Signer full Distinguished Name: CN=RODRIGO MARQUES TEIXEIRA
  - Signing Time: Feb 07 2024 18:35:56
  - Signing Hash Algorithm: SHA-256
  - Signature Type: adbe.pkcs7.detached
  - Signed Ranges: [0 - 250056], [269002 - 280240]
  - Total document signed
  - Signature Validation: Signature is Valid.
  - Certificate Validation: Certificate has Expired
```

Do contrário seria:

`File 'documento0.pdf' does not contain any signatures`
