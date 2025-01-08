<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\InboundRecord;
use App\Models\OutboundRecord;
use App\Models\InboundItem;
use App\Models\OutboundItem;
use App\Models\Item;
use App\Models\BatchItem;
use App\Models\Purpose;
use App\Models\BatchItemHistory;
use App\Models\PartNumber;
use Illuminate\Database\Seeder;

class CombinedTransactionSeeder extends Seeder
{
    private array $locations = [
        'Gudang Jakarta' => [
            'address' => 'Jl. Raya Kelapa Gading No. 123, Jakarta Utara',
            'weight' => 70,
        ],
        'Gudang Surabaya' => [
            'address' => 'Jl. Rungkut Industri No. 45, Surabaya',
            'weight' => 30,
        ],
    ];

    // Part number untuk perlengkapan yang akan masuk ke Batch Item
    private array $batchItemPartNumbers = [
        'PC-C6-05M', 'PC-C6-1M', 'PC-C6-2M', 'PC-C6-3M', 'PC-C6-5M', 
        'UTP-C6-305M', 'PDU-15A', 'CABLE-MGR-1U', 'CABLE-MGR-2U',
        'BLANK-1U', 'BLANK-2U'
    ];

    private function generateSerialNumber($brand, $partNumber): string
    {
        $serialFormats = [
            'Cisco' => [
                'prefix' => 'FOC',
                'format' => 'FOC%s%04d'
            ],
            'Fortinet' => [
                'prefix' => 'FGT',
                'format' => 'FGT%sT%06d'
            ],
            'HPE' => [
                'prefix' => 'SGH',
                'format' => 'SGH%s%05d'
            ],
            'Juniper' => [
                'prefix' => 'JN',
                'format' => 'JN%s%06d'
            ],
        ];

        $format = $serialFormats[$brand] ?? [
            'prefix' => 'SN',
            'format' => 'SN%s%06d'
        ];

        $timestamp = strtoupper(substr(md5(microtime()), 0, 4));
        $sequence = rand(1, 99999);

        return sprintf($format['format'], $timestamp, $sequence);
    }

    private function getRandomLocation(): string
    {
        $rand = rand(1, 100);
        $currentWeight = 0;
        
        foreach ($this->locations as $location => $data) {
            $currentWeight += $data['weight'];
            if ($rand <= $currentWeight) {
                return $location;
            }
        }
        
        return 'Gudang Jakarta';
    }

    private function isBatchItem($partNumber): bool
    {
        return in_array($partNumber->part_number, $this->batchItemPartNumbers);
    }

    public function run(): void
    {
        $purchaseOrders = PurchaseOrder::all();
        $purposes = Purpose::all();
        $partNumbers = PartNumber::with('brand')->get();
        $defaultFormat = \App\Models\UnitFormat::where('name', 'PCS')->first();
        $lpbCounter = 1;
        $lkbCounter = 1;

        // Inisialisasi BatchItem untuk semua perlengkapan
        foreach ($partNumbers as $partNumber) {
            if ($this->isBatchItem($partNumber)) {
                BatchItem::firstOrCreate(
                    ['part_number_id' => $partNumber->part_number_id],
                    ['quantity' => 0, 'format_id' => $defaultFormat->format_id]
                );
            }
        }

        foreach ($purchaseOrders as $po) {
            for ($i = 0; $i < rand(2, 4); $i++) {
                $location = $this->getRandomLocation();
                $inboundDate = $po->po_date->addDays(rand(1, 14));
                
                $inbound = InboundRecord::create([
                    'lpb_number' => 'LPB-' . str_pad($lpbCounter++, 3, '0', STR_PAD_LEFT),
                    'receive_date' => $inboundDate,
                    'po_id' => $po->po_id,
                    'project_id' => $po->project_id,
                    'location' => $location
                ]);

                // Inbound untuk perangkat (dengan SN)
                $equipmentPartNumbers = $partNumbers->filter(fn($pn) => !$this->isBatchItem($pn))->random(rand(1, 2));
                foreach ($equipmentPartNumbers as $equipment) {
                    // Buat 2-3 item dengan SN berbeda untuk setiap part number
                    for ($j = 0; $j < rand(2, 3); $j++) {
                        $item = Item::create([
                            'part_number_id' => $equipment->part_number_id,
                            'serial_number' => $this->generateSerialNumber(
                                $equipment->brand->brand_name,
                                $equipment->part_number
                            ),
                            'status' => 'diterima'
                        ]);

                        InboundItem::create([
                            'inbound_id' => $inbound->inbound_id,
                            'item_id' => $item->item_id,
                            'quantity' => 1 // Selalu 1 karena per SN
                        ]);
                    }
                }

                // Inbound untuk batch items (tanpa SN)
                $batchPartNumbers = $partNumbers->filter(fn($pn) => $this->isBatchItem($pn))->random(2);
                foreach ($batchPartNumbers as $batchPart) {
                    $quantity = rand(10, 50);
                    
                    // Update quantity di BatchItem
                    $batchItem = BatchItem::updateQuantity(
                        $batchPart->part_number_id,
                        $quantity,
                        'inbound',
                        $inbound
                    );

                    // Buat satu dummy item untuk batch item ini jika belum ada
                    $dummyItem = Item::firstOrCreate(
                        [
                            'part_number_id' => $batchPart->part_number_id,
                            'serial_number' => 'BATCH-' . strtoupper(substr(md5($batchPart->part_number), 0, 8)),
                        ],
                        ['status' => 'diterima']
                    );

                    // Catat di InboundItem
                    InboundItem::create([
                        'inbound_id' => $inbound->inbound_id,
                        'item_id' => $dummyItem->item_id,
                        'quantity' => $quantity
                    ]);
                }

                // Outbound process (70% chance)
                if (rand(1, 100) <= 70) {
                    $outboundDate = $inboundDate->copy()->addDays(rand(1, 30));
                    
                    $outbound = OutboundRecord::create([
                        'lkb_number' => 'LKB-' . str_pad($lkbCounter++, 3, '0', STR_PAD_LEFT),
                        'delivery_date' => $outboundDate,
                        'vendor_id' => $po->project->vendor_id,
                        'project_id' => $po->project_id,
                        'purpose_id' => $purposes->random()->purpose_id
                    ]);

                    // Outbound untuk perangkat (dengan SN)
                    $itemsToOutbound = Item::where('status', 'diterima')
                        ->whereHas('partNumber', function($query) {
                            $query->whereNotIn('part_number', $this->batchItemPartNumbers);
                        })
                        ->whereHas('inboundItems', function($query) use ($inboundDate) {
                            $query->whereHas('inboundRecord', function($q) use ($inboundDate) {
                                $q->where('receive_date', '<=', $inboundDate);
                            });
                        })
                        ->take(rand(1, 2)) // Ambil 1-2 item
                        ->get();

                    foreach ($itemsToOutbound as $item) {
                        OutboundItem::create([
                            'outbound_id' => $outbound->outbound_id,
                            'item_id' => $item->item_id,
                            'quantity' => 1 // Selalu 1 karena per SN
                        ]);

                        $randomNum = rand(1, 3);
                        $newStatus = match($randomNum) {
                            1 => 'masa_sewa',
                            2 => 'terjual',
                            3 => 'dipinjam',
                        };
                        $item->update(['status' => $newStatus]);
                    }

                    // Outbound untuk batch items
                    foreach ($batchPartNumbers as $batchPart) {
                        $batchItem = BatchItem::where('part_number_id', $batchPart->part_number_id)->first();
                        if ($batchItem && $batchItem->quantity > 0) {
                            $outQuantity = min(rand(5, 10), $batchItem->quantity);
                            
                            // Ambil dummy item yang sudah dibuat sebelumnya
                            $dummyItem = Item::where('part_number_id', $batchPart->part_number_id)
                                ->where('serial_number', 'like', 'BATCH-%')
                                ->first();

                            // Catat di OutboundItem
                            OutboundItem::create([
                                'outbound_id' => $outbound->outbound_id,
                                'item_id' => $dummyItem->item_id,
                                'quantity' => $outQuantity
                            ]);

                            BatchItem::updateQuantity(
                                $batchPart->part_number_id,
                                $outQuantity,
                                'outbound',
                                $outbound
                            );
                        }
                    }
                }
            }
        }
    }
} 