<?php

namespace App\Etic\Catalog\Spreadsheet;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class TrendyolWorkbook
{
    /**
     * @var array<string, string>
     */
    public const HEADERS = [
        'partner id' => 'partner_id',
        'barkod' => 'sku',
        'model kodu' => 'model_code',
        'ürün rengi' => 'color',
        'urun rengi' => 'color',
        'beden' => 'size',
        'marka' => 'brand',
        'kategori ismi' => 'category',
        'ürün adı' => 'name',
        'urun adi' => 'name',
        'ürün açıklaması' => 'description',
        'urun aciklamasi' => 'description',
        'satış fiyatı' => 'price',
        'satis fiyati' => 'price',
        'ürün stok adedi' => 'stock',
        'urun stok adedi' => 'stock',
        'kdv oranı' => 'vat',
        'kdv orani' => 'vat',
        'görsel 1' => 'image_1',
        'gorsel 1' => 'image_1',
        'görsel 2' => 'image_2',
        'gorsel 2' => 'image_2',
        'görsel 3' => 'image_3',
        'gorsel 3' => 'image_3',
        'görsel 4' => 'image_4',
        'gorsel 4' => 'image_4',
        'görsel 5' => 'image_5',
        'gorsel 5' => 'image_5',
        'görsel 6' => 'image_6',
        'gorsel 6' => 'image_6',
        'görsel 7' => 'image_7',
        'gorsel 7' => 'image_7',
        'görsel 8' => 'image_8',
        'gorsel 8' => 'image_8',
    ];

    /**
     * @return list<string>
     */
    public static function templateHeaders(): array
    {
        return [
            'Partner ID',
            'Barkod',
            'Model Kodu',
            'Ürün Rengi',
            'Beden',
            'Marka',
            'Kategori İsmi',
            'Ürün Adı',
            'Ürün Açıklaması',
            'Satış Fiyatı',
            'Ürün Stok Adedi',
            'KDV Oranı',
            'Görsel 1',
            'Görsel 2',
            'Görsel 3',
            'Görsel 4',
            'Görsel 5',
            'Görsel 6',
            'Görsel 7',
            'Görsel 8',
        ];
    }

    /**
     * @return Collection<int, array{row: int, sku: string, model_code: string, color: string, size: string, brand: ?string, category: ?string, name: string, description: ?string, price: ?string, stock: ?string, vat: ?string, images: list<string>, partner_id: ?string}>
     */
    public function parse(string $path): Collection
    {
        $grid = $this->sheetGrid($path);

        if ($grid === []) {
            throw new InvalidArgumentException('Excel dosyası boş.');
        }

        $headerRow = array_shift($grid);
        $map = [];

        foreach ($headerRow as $index => $label) {
            $key = self::HEADERS[$this->normalizeHeader((string) $label)] ?? null;

            if ($key) {
                $map[$key] = $index;
            }
        }

        if (! isset($map['sku'], $map['model_code'])) {
            throw new InvalidArgumentException('Excel şablonunda Barkod ve Model Kodu kolonları zorunludur.');
        }

        return collect($grid)
            ->map(function (array $cells, int $index) use ($map): ?array {
                $row = $index + 2;
                $sku = trim((string) ($cells[$map['sku']] ?? ''));
                $modelCode = trim((string) ($cells[$map['model_code']] ?? ''));

                if ($sku === '' && $modelCode === '') {
                    return null;
                }

                $images = [];

                for ($i = 1; $i <= 8; $i++) {
                    $url = $this->imageUrl((string) ($cells[$map['image_'.$i] ?? -1] ?? ''));

                    if ($url !== null) {
                        $images[] = $url;
                    }
                }

                return [
                    'row' => $row,
                    'sku' => $sku,
                    'model_code' => $modelCode,
                    'color' => trim((string) ($cells[$map['color'] ?? -1] ?? '')),
                    'size' => trim((string) ($cells[$map['size'] ?? -1] ?? '')),
                    'brand' => $this->nullable($cells[$map['brand'] ?? -1] ?? null),
                    'category' => $this->nullable($cells[$map['category'] ?? -1] ?? null),
                    'name' => trim((string) ($cells[$map['name'] ?? -1] ?? '')),
                    'description' => $this->nullable($cells[$map['description'] ?? -1] ?? null),
                    'price' => $this->nullable($cells[$map['price'] ?? -1] ?? null),
                    'stock' => $this->nullable($cells[$map['stock'] ?? -1] ?? null),
                    'vat' => $this->nullable($cells[$map['vat'] ?? -1] ?? null),
                    'images' => $images,
                    'partner_id' => $this->nullable($cells[$map['partner_id'] ?? -1] ?? null),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function write(array $rows, ?string $path = null): string
    {
        $path ??= sys_get_temp_dir().'/etic-urun-sablonu-'.uniqid('', true).'.xlsx';
        $lines = [self::templateHeaders(), ...array_map(fn (array $row) => $this->exportRow($row), $rows)];
        $sheet = $this->sheetXml($lines);

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Excel dosyası yazılamadı.');
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Ürünler" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $path;
    }

    /**
     * @return list<list<string>>
     */
    private function sheetGrid(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException('Excel dosyası bulunamadı.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Excel dosyası okunamadı.');
        }

        $strings = [];

        if (($shared = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($shared);

            if ($xml) {
                foreach ($xml->xpath('//*[local-name()="si"]') ?: [] as $si) {
                    $texts = [];

                    foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                        $texts[] = (string) $text;
                    }

                    $strings[] = implode('', $texts);
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml')
            ?: $zip->getFromName('xl/worksheets/sheet.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new InvalidArgumentException('Excel çalışma sayfası okunamadı.');
        }

        $sheet = simplexml_load_string($sheetXml);

        if (! $sheet) {
            throw new InvalidArgumentException('Excel çalışma sayfası geçersiz.');
        }

        $rows = [];

        foreach ($sheet->xpath('//*[local-name()="c"]') ?: [] as $cell) {
            $ref = (string) $cell['r'];

            if (! preg_match('/^([A-Z]+)(\d+)$/', $ref, $match)) {
                continue;
            }

            $col = $this->columnIndex($match[1]);
            $row = (int) $match[2];
            $type = (string) $cell['t'];
            $value = '';
            $v = $cell->xpath('.//*[local-name()="v"]');
            $formula = $cell->xpath('.//*[local-name()="f"]');

            if ($type === 's') {
                $value = $strings[(int) ($v[0] ?? 0)] ?? '';
            } elseif ($type === 'inlineStr') {
                $texts = [];

                foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                    $texts[] = (string) $text;
                }

                $value = implode('', $texts);
            } elseif (isset($v[0])) {
                $value = (string) $v[0];
            }

            if ($value === '' && isset($formula[0])) {
                $value = (string) $formula[0];
            }

            $rows[$row][$col] = $value;
        }

        ksort($rows);

        return array_map(function (array $cells): array {
            $max = $cells === [] ? 0 : max(array_keys($cells));
            $line = [];

            for ($i = 0; $i <= $max; $i++) {
                $line[$i] = $cells[$i] ?? '';
            }

            return $line;
        }, $rows);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $cells) {
            $r = $rowIndex + 1;
            $xml .= '<row r="'.$r.'">';

            foreach ($cells as $colIndex => $value) {
                $ref = $this->columnLetter($colIndex).$r;
                $xml .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function exportRow(array $row): array
    {
        $images = array_values($row['images'] ?? []);

        return [
            (string) ($row['partner_id'] ?? ''),
            (string) ($row['sku'] ?? ''),
            (string) ($row['model_code'] ?? ''),
            (string) ($row['color'] ?? ''),
            (string) ($row['size'] ?? ''),
            (string) ($row['brand'] ?? ''),
            (string) ($row['category'] ?? ''),
            (string) ($row['name'] ?? ''),
            (string) ($row['description'] ?? ''),
            (string) ($row['price'] ?? ''),
            (string) ($row['stock'] ?? ''),
            (string) ($row['vat'] ?? ''),
            (string) ($images[0] ?? ''),
            (string) ($images[1] ?? ''),
            (string) ($images[2] ?? ''),
            (string) ($images[3] ?? ''),
            (string) ($images[4] ?? ''),
            (string) ($images[5] ?? ''),
            (string) ($images[6] ?? ''),
            (string) ($images[7] ?? ''),
        ];
    }

    private function normalizeHeader(string $label): string
    {
        return trim(strtolower(Str::ascii($label)));
    }

    private function columnIndex(string $letters): int
    {
        $n = 0;

        foreach (str_split($letters) as $letter) {
            $n = ($n * 26) + (ord($letter) - 64);
        }

        return $n - 1;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index++;

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function imageUrl(string $value): ?string
    {
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($value === '') {
            return null;
        }

        if (preg_match('/https?:\/\/[^\s"\'<>]+/i', $value, $match) !== 1) {
            return null;
        }

        $url = rtrim($match[0], '.,);');

        return filter_var($url, FILTER_VALIDATE_URL) ?: null;
    }
}
