<?php

namespace KiboImex;

use Generator;
use IteratorAggregate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportRowCollection implements IteratorAggregate {

    private $rows = [];

    public function addRow(ExportRow $row): self {
        $newCollection = new self;
        $newCollection->rows = $this->rows;
        $newCollection->rows[] = $row;
        return $newCollection;
    }

    /** @phan-suppress-next-line PhanUnreferencedPublicMethod */
    public function writeSheet(Worksheet $sheet) {
        $i = 1;
        $columns = [];
        foreach ($this->getHeader() as $label => $value) {
            $columns[$label] = $i++;
        }
        $rowNumber = 1;
        foreach ($this->getRowsWithHeader() as $row) {
            foreach ($row as $label => $value) {
                if ($value === '') {
                    continue;
                }
                $sheet->setCellValueByColumnAndRow($columns[$label], $rowNumber, $value);
            }
            $rowNumber++;
        }
    }

    public function getIterator(): Generator {
        yield from $this->rows;
    }

    public function getRowsWithHeader(): Generator {
        yield $this->getHeader();
        yield from $this;
    }

    public function getHeader(): ExportRow {
        $rowUnion = new ExportRow();
        foreach ($this->rows as $row) {
            $rowUnion = $rowUnion->mergeReplace($row);
        }
        $header = new ExportRow();
        /** @phan-suppress-next-line PhanUnusedVariableValueOfForeachWithKey */
        foreach ($rowUnion as $label => $value) {
            $header = $header->addField(0, $label, $label);
        }
        return $header;
    }

    /** @phan-suppress-next-line PhanUnreferencedPublicMethod */
    public static function fromSheet(Worksheet $sheet): self {
        $rows = $sheet->toArray(null, true, false, true);
        $rows = array_map(function (array $row): array {
            return array_map(function ($column): string {
                return trim((string) $column);
            }, $row);
        }, $rows);
        $rows = array_filter($rows, function(array $row): bool {
            foreach ($row as $column) {
                if ($column !== '') {
                    return true;
                }
            }
            return false;
        });
        if ($rows) {
            $headerRow = array_keys($rows)[0];
            $header = $rows[$headerRow];
            unset($rows[$headerRow]);
        } else {
            $header = [];
        }
        $collection = new self;
        foreach ($rows as $row) {
            $rowObject = new ExportRow();
            foreach ($header as $column => $label) {
                if ($label === '') {
                    continue;
                }
                $rowObject = $rowObject->addField(0, $label, $row[$column] ?? '');
            }
            $collection = $collection->addRow($rowObject);
        }
        return $collection;
    }

}