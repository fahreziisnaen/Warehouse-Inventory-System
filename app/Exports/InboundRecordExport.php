<?php

namespace App\Exports;

use App\Models\InboundRecord;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Maatwebsite\Excel\Concerns\Exportable;

class InboundRecordExport
{
    use Exportable;

    protected $inboundRecord;
    const ITEMS_PER_PAGE = 15;
    const TABLE_HEADER_ROW = 10;
    const ITEMS_START_ROW = 12;
    const TEMPLATE_ROW = 12;
    const COL_NO = 'B';
    const COL_PART_NO = 'C';
    const COL_DESC_START = 'D';
    const COL_DESC_END = 'E';
    const COL_QTY = 'F';
    const COL_BRAND_START = 'H';
    const COL_BRAND_END = 'I';
    const COL_SERIAL = 'J';
    const NOTE_ROW = 13;
    const NOTE_COL = 'B';

    public function __construct(InboundRecord $inboundRecord)
    {
        $this->inboundRecord = $inboundRecord;
    }

    public function download()
    {
        // Load template
        $spreadsheet = IOFactory::load(storage_path('app/templates/lpb_template.xlsx'));
        $sheet = $spreadsheet->getActiveSheet();

        // Replace placeholders
        $replacements = [
            '[LPB_NUMBER]' => $this->inboundRecord->lpb_number,
            '[RECEIVE_DATE]' => $this->inboundRecord->receive_date->format('d-m-Y'),
            '[PO_NUMBER]' => $this->inboundRecord->purchaseOrder?->po_number ?? '-',
            '[PO_DATE]' => $this->inboundRecord->purchaseOrder?->po_date?->format('d-m-Y') ?? '-',
            '[VENDOR_NAME]' => $this->getVendorName(),
            '[PROJECT_ID]' => $this->inboundRecord->project->project_id,
            '[NOTE]' => "Note :\n" . ($this->inboundRecord->note ?? '-'),
        ];

        $this->replaceInWorksheet($sheet, $replacements);

        // Process items
        $currentRow = self::ITEMS_START_ROW; // Row 12
        $rowNumber = 1;

        // Get items
        $serialItems = $this->inboundRecord->validInboundItems()
            ->with(['item.partNumber.brand'])
            ->orderBy('inbound_item_id')
            ->get();

        // Get batch items
        $batchItems = $this->inboundRecord->batchItemHistories()
            ->with(['batchItem.partNumber.brand', 'batchItem.unitFormat'])
            ->orderBy('history_id')
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
                // Copy row height
                $sheet->getRowDimension($i)->setRowHeight(
                    $sheet->getRowDimension(12)->getRowHeight()
                );

                // Copy style sekali untuk seluruh range (ubah A-K menjadi A-J)
                $sheet->duplicateStyle(
                    $sheet->getStyle('A12:J12'),
                    'A' . $i . ':J' . $i
                );

                // Merge cells yang diperlukan
                $sheet->mergeCells('D' . $i . ':E' . $i);  // Description
                $sheet->mergeCells('H' . $i . ':I' . $i);  // Brand
            }

            // Apply borders untuk seluruh area data sekaligus (ubah A-K menjadi A-J)
            $lastRow = 12 + $totalRows - 1;
            $sheet->getStyle('A12:J' . $lastRow)->applyFromArray([
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
            $sheet->setCellValue(self::COL_PART_NO . $currentRow, 
                $firstItem->item->condition === 'Bekas' 
                    ? sprintf('%s [Bks]', $partNumber)
                    : $partNumber
            );
            $sheet->setCellValue(self::COL_DESC_START . $currentRow, $firstItem->item->partNumber->description);
            $sheet->setCellValue(self::COL_QTY . $currentRow, $items->count());
            $sheet->setCellValue('G' . $currentRow, 'Unit');
            $sheet->setCellValue(self::COL_BRAND_START . $currentRow, $firstItem->item->partNumber->brand->brand_name);
            $sheet->setCellValue(self::COL_SERIAL . $currentRow, $firstItem->item->serial_number);
            
            $currentRow++;

            // Row berikutnya untuk serial number yang tersisa
            foreach ($items->skip(1) as $item) {
                $sheet->setCellValue(self::COL_SERIAL . $currentRow, $item->item->serial_number);
                $currentRow++;
            }

            $rowNumber++;
        }

        // Proses Batch Items
        foreach ($groupedBatchItems as $partNumber => $histories) {
            $firstHistory = $histories->first();
            
            $sheet->setCellValue(self::COL_NO . $currentRow, $rowNumber);
            $sheet->setCellValue(self::COL_PART_NO . $currentRow, $partNumber);
            $sheet->setCellValue(self::COL_DESC_START . $currentRow, $firstHistory->batchItem->partNumber->description);
            $totalQuantity = $histories->sum('quantity');
            $sheet->setCellValue(self::COL_QTY . $currentRow, $totalQuantity);
            $sheet->setCellValue('G' . $currentRow, $firstHistory->batchItem->unitFormat->name);
            $sheet->setCellValue(self::COL_BRAND_START . $currentRow, $firstHistory->batchItem->partNumber->brand->brand_name);
            $sheet->setCellValue(self::COL_SERIAL . $currentRow, '-');
            
            $currentRow++;
            $rowNumber++;
        }

        // Create writer and save
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'LPB_' . $this->inboundRecord->lpb_number . '.xlsx';
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
                                $richText->createText("\n" . ($this->inboundRecord->note ?? '-'));
                                
                                $cell->setValue($richText);
                            } else {
                                $cell->setValue(str_replace($search, $replace, $value));
                            }
                            $cell->getStyle()->getAlignment()->setWrapText(true);
                        }
                    }
                }
            }
        }
    }

    private function getVendorName(): string 
    {
        // Jika ada PO, ambil vendor supplier dari PO
        if ($this->inboundRecord->purchaseOrder) {
            return $this->inboundRecord->purchaseOrder->vendor->vendor_name;
        }
        
        // Jika tidak ada PO, ambil vendor customer dari project
        return $this->inboundRecord->project->vendor->vendor_name;
    }
} 