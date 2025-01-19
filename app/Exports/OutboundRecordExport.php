<?php

namespace App\Exports;

use App\Models\OutboundRecord;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class OutboundRecordExport
{
    use Exportable;

    protected $outboundRecord;
    const ITEMS_PER_PAGE = 15;
    const TABLE_HEADER_ROW = 10;
    const ITEMS_START_ROW = 12;
    const TEMPLATE_ROW = 12;
    const COL_NO = 'B';
    const COL_PART_NO = 'C';
    const COL_DESC = 'D';
    const COL_QTY = 'F';
    const COL_UNIT = 'G';
    const COL_BRAND = 'H';
    const COL_SERIAL = 'I';
    const COL_LPB = 'J';
    const COL_PURPOSE = 'K';

    public function __construct(OutboundRecord $outboundRecord)
    {
        $this->outboundRecord = $outboundRecord;
    }

    private function formatStatus(string $status): string
    {
        return match($status) {
            'masa_sewa' => 'Sewa',
            'non_sewa' => 'Non Sewa',
            'dipinjam' => 'Peminjaman',
            default => ucfirst($status)
        };
    }

    public function download()
    {
        // Load template
        $spreadsheet = IOFactory::load(storage_path('app/templates/lkb_template.xlsx'));
        $sheet = $spreadsheet->getActiveSheet();

        // Replace placeholders
        $replacements = [
            '[LKB_NUMBER]' => $this->outboundRecord->lkb_number,
            '[PROJECT_NAME]' => $this->outboundRecord->project->project_name ?? '-',
            '[PROJECT_ID]' => $this->outboundRecord->project->project_id ?? '-',
            '[VENDOR_NAME]' => $this->outboundRecord->vendor->vendor_name ?? '-',
            '[DELIVERY_DATE]' => $this->outboundRecord->delivery_date->format('d-m-Y'),
            '[DELIVERY_NOTE]' => $this->outboundRecord->delivery_note_number ?? '-',
            '[DELIVERY_NOTE_NUMBER]' => $this->outboundRecord->delivery_note_number ?? '-',
            '[NOTE]' => $this->outboundRecord->note ?? '-',
        ];

        $this->replaceInWorksheet($sheet, $replacements);

        // Process items
        $currentRow = self::ITEMS_START_ROW;
        $rowNumber = 1;

        // Get items
        $serialItems = $this->outboundRecord->outboundItems()
            ->with(['item.partNumber.brand', 'inboundItem.inboundRecord'])
            ->get();

        // Get batch items
        $batchItems = $this->outboundRecord->batchItemHistories()
            ->with(['batchItem.partNumber.brand', 'batchItem.inboundHistories.recordable'])
            ->get();

        // Kelompokkan Serial Items berdasarkan part number
        $groupedSerialItems = $serialItems->groupBy(function ($item) {
            return $item->item->partNumber->part_number;
        });

        // Kelompokkan Batch Items berdasarkan part number
        $groupedBatchItems = $batchItems->groupBy(function ($history) {
            return $history->batchItem->partNumber->part_number;
        });

        // Hitung total baris yang dibutuhkan
        $totalRows = $serialItems->count() + $batchItems->count();

        // Duplikasi row template jika diperlukan
        if ($totalRows > 1) {
            $sheet->insertNewRowBefore(13, $totalRows - 1);
            
            // Copy format dari row template ke baris baru
            for ($i = 13; $i < 13 + $totalRows - 1; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(
                    $sheet->getRowDimension(12)->getRowHeight()
                );

                $sheet->duplicateStyle(
                    $sheet->getStyle('A12:K12'),
                    'A' . $i . ':K' . $i
                );

                $sheet->mergeCells('D' . $i . ':E' . $i);
            }

            // Apply borders
            $lastRow = 12 + $totalRows - 1;
            $sheet->getStyle('A12:K' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Isi data items
        foreach ($groupedSerialItems as $partNumber => $items) {
            $firstItem = $items->first();
            
            // Row pertama dari part number
            $sheet->setCellValue(self::COL_NO . $currentRow, $rowNumber);
            $sheet->setCellValue(self::COL_PART_NO . $currentRow, $partNumber);
            $sheet->setCellValue(self::COL_DESC . $currentRow, $firstItem->item->partNumber->description);
            $sheet->setCellValue(self::COL_QTY . $currentRow, $items->count());
            $sheet->setCellValue(self::COL_UNIT . $currentRow, 'Unit');
            $sheet->setCellValue(self::COL_BRAND . $currentRow, $firstItem->item->partNumber->brand->brand_name);
            $sheet->setCellValue(self::COL_SERIAL . $currentRow, $firstItem->item->serial_number);
            $sheet->setCellValue(self::COL_LPB . $currentRow, $firstItem->inboundItem?->inboundRecord?->lpb_number ?? '-');
            $sheet->setCellValue(self::COL_PURPOSE . $currentRow, $firstItem->purpose->name);
            
            $currentRow++;

            // Row berikutnya untuk serial number yang tersisa
            foreach ($items->skip(1) as $item) {
                $sheet->setCellValue(self::COL_SERIAL . $currentRow, $item->item->serial_number);
                $sheet->setCellValue(self::COL_LPB . $currentRow, $item->inboundItem?->inboundRecord?->lpb_number ?? '-');
                $sheet->setCellValue(self::COL_PURPOSE . $currentRow, $item->purpose->name);
                $currentRow++;
            }

            $rowNumber++;
        }

        // Proses Batch Items
        foreach ($groupedBatchItems as $partNumber => $histories) {
            $firstHistory = $histories->first();
            
            $sheet->setCellValue(self::COL_NO . $currentRow, $rowNumber);
            $sheet->setCellValue(self::COL_PART_NO . $currentRow, $partNumber);
            $sheet->setCellValue(self::COL_DESC . $currentRow, $firstHistory->batchItem->partNumber->description);
            $totalQuantity = abs($histories->sum('quantity'));
            $sheet->setCellValue(self::COL_QTY . $currentRow, $totalQuantity);
            $sheet->setCellValue(self::COL_UNIT . $currentRow, $firstHistory->batchItem->unitFormat->name);
            $sheet->setCellValue(self::COL_BRAND . $currentRow, $firstHistory->batchItem->partNumber->brand->brand_name);
            $sheet->setCellValue(self::COL_SERIAL . $currentRow, '-');
            $sheet->setCellValue(self::COL_LPB . $currentRow, $firstHistory->batchItem->inboundHistories->first()?->recordable?->lpb_number ?? '-');
            $sheet->setCellValue(self::COL_PURPOSE . $currentRow, '-');
            
            $currentRow++;
            $rowNumber++;
        }

        // Create writer and save
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'LKB_' . $this->outboundRecord->lkb_number . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    private function replaceInWorksheet($worksheet, $replacements)
    {
        foreach ($worksheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                if (is_string($value)) {
                    foreach ($replacements as $search => $replace) {
                        if (strpos($value, $search) !== false) {
                            if ($search === '[NOTE]') {
                                // Buat RichText untuk note
                                $richText = new RichText();
                                $bold = $richText->createTextRun("Note :");
                                $bold->getFont()->setBold(true);
                                $richText->createText("\n" . ($this->outboundRecord->note ?? '-'));
                                
                                $cell->setValue($richText);
                                $cell->getStyle()->getAlignment()->setWrapText(true);
                            } else {
                                $cell->setValue(str_replace($search, $replace, $value));
                            }
                        }
                    }
                }
            }
        }
    }
} 