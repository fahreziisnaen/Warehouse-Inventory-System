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

    private array $accessoryPartNumbers = [
        'CAB-CONSOLE-USB', 'CAB-ETH-S-RJ45', 'SFP-10G-AOC3M', 'CAB-AC-250V',
        'STACK-T1-50CM', 'SP-FG60F', 'SP-FG100F', 'SP-CABLE-USB', 'SP-RACKMOUNT',
        'PC-C6-5M-BL', 'PC-C6-3M-BL', 'PC-C6-1M-BL', 'DAC-10G-3M', 'DAC-10G-5M',
        'SFP-10G-SR', 'SFP-1G-T'
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

    private function createItemsForInbound($partNumber, $count)
    {
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = Item::create([
                'part_number_id' => $partNumber->part_number_id,
                'serial_number' => $this->generateSerialNumber(
                    $partNumber->brand->brand_name,
                    $partNumber->part_number
                ),
                'status' => 'baru'
            ]);
        }
        return $items;
    }

    private function isAccessory($partNumber): bool
    {
        return in_array($partNumber->part_number, $this->accessoryPartNumbers);
    }

    public function run(): void
    {
        $purchaseOrders = PurchaseOrder::all();
        $purposes = Purpose::all();
        $partNumbers = PartNumber::with('brand')->get();
        $defaultFormat = \App\Models\UnitFormat::where('name', 'PCS')->first();

        // Buat BatchItem untuk semua part number terlebih dahulu
        foreach ($partNumbers as $partNumber) {
            BatchItem::firstOrCreate(
                ['part_number_id' => $partNumber->part_number_id],
                ['quantity' => 0, 'format_id' => $defaultFormat->format_id]
            );
        }

        foreach ($purchaseOrders as $po) {
            // 4-6 transaksi per PO
            for ($i = 0; $i < rand(4, 6); $i++) {
                $location = $this->getRandomLocation();
                $inboundDate = $po->po_date->addDays(rand(1, 14));
                
                // Buat satu inbound record
                $inbound = InboundRecord::create([
                    'lpb_number' => 'LPB-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'receive_date' => $inboundDate,
                    'po_id' => $po->po_id,
                    'project_id' => $po->project_id,
                    'location' => $location
                ]);

                // Pilih 1-2 perangkat dan 2-3 perlengkapan
                $equipmentPartNumbers = $partNumbers->filter(function($pn) {
                    return !$this->isAccessory($pn);
                })->random(rand(1, 2));

                $accessoryPartNumbers = $partNumbers->filter(function($pn) {
                    return $this->isAccessory($pn);
                })->random(rand(2, 3));

                // Proses inbound perangkat
                foreach ($equipmentPartNumbers as $partNumber) {
                    $items = $this->createItemsForInbound($partNumber, rand(2, 4));
                    
                    foreach ($items as $item) {
                        InboundItem::create([
                            'inbound_id' => $inbound->inbound_id,
                            'item_id' => $item->item_id,
                            'quantity' => 1
                        ]);

                        $item->update(['status' => 'diterima']);

                        // Batch item sudah ada, langsung update quantity
                        BatchItem::updateQuantity(
                            $partNumber->part_number_id,
                            1,
                            'inbound',
                            $inbound
                        );
                    }
                }

                // Proses inbound perlengkapan
                foreach ($accessoryPartNumbers as $partNumber) {
                    $dummyItem = Item::create([
                        'part_number_id' => $partNumber->part_number_id,
                        'serial_number' => 'BATCH-' . strtoupper(substr(md5(microtime()), 0, 8)),
                        'status' => 'diterima'
                    ]);

                    $inboundQty = rand(50, 200);
                    
                    InboundItem::create([
                        'inbound_id' => $inbound->inbound_id,
                        'item_id' => $dummyItem->item_id,
                        'quantity' => $inboundQty
                    ]);

                    // Batch item sudah ada, langsung update quantity
                    BatchItem::updateQuantity(
                        $partNumber->part_number_id,
                        $inboundQty,
                        'inbound',
                        $inbound
                    );
                }

                // Outbound (80% chance)
                if (rand(1, 100) <= 80) {
                    $outboundDate = $inboundDate->copy()->addDays(rand(1, 30));
                    
                    $outbound = OutboundRecord::create([
                        'lkb_number' => 'LKB-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                        'delivery_date' => $outboundDate,
                        'vendor_id' => $po->project->vendor_id,
                        'project_id' => $po->project_id,
                        'purpose_id' => $purposes->random()->purpose_id
                    ]);

                    // Outbound perangkat
                    $itemsToOutbound = Item::where('status', 'diterima')
                        ->whereHas('inboundItems', function($query) use ($inboundDate) {
                            $query->whereHas('inboundRecord', function($q) use ($inboundDate) {
                                $q->where('receive_date', '<=', $inboundDate);
                            });
                        })
                        ->take(rand(1, 2))
                        ->get();

                    foreach ($itemsToOutbound as $item) {
                        OutboundItem::create([
                            'outbound_id' => $outbound->outbound_id,
                            'item_id' => $item->item_id,
                            'quantity' => 1
                        ]);

                        $randomNum = rand(1, 3);
                        $newStatus = match($randomNum) {
                            1 => 'masa_sewa',
                            2 => 'terjual',
                            3 => 'dipinjam',
                        };
                        $item->update(['status' => $newStatus]);

                        BatchItem::updateQuantity(
                            $item->part_number_id,
                            1,
                            'outbound',
                            $outbound
                        );
                    }

                    // Outbound perlengkapan dalam outbound yang sama
                    foreach ($accessoryPartNumbers as $partNumber) {
                        $batchItem = BatchItem::where('part_number_id', $partNumber->part_number_id)->first();
                        if ($batchItem && $batchItem->quantity > 0) {
                            // Cari dummy item untuk part number ini
                            $dummyItem = Item::where('part_number_id', $partNumber->part_number_id)
                                ->where('serial_number', 'like', 'BATCH-%')
                                ->first();

                            $outboundQty = rand(5, min(20, $batchItem->quantity));
                            
                            OutboundItem::create([
                                'outbound_id' => $outbound->outbound_id,
                                'item_id' => $dummyItem->item_id,  // Gunakan dummy item yang sama
                                'quantity' => $outboundQty
                            ]);

                            BatchItem::updateQuantity(
                                $partNumber->part_number_id,
                                $outboundQty,
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