<?php

namespace App\Console\Commands;

use App\Models\CreatedCsv;
use App\Models\Shipping;
use Illuminate\Console\Command;

class OrdersHandle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:handle {--new}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create .csv file from xml data, add record to database';

    protected $orderPath;
    protected $shippingPath;

    protected const CSV_HEADING = ['order_id', 'type', 'tracking_number', 'sending_date', 'shipmentNo'];

    protected $xmlDir;
    protected $csvFileResource;
    protected $csvFileName;

    protected $count = 0;

    //Проверка
    protected function isInCsvTable(int $shippingID): bool
    {
        return (bool)CreatedCsv::firstWhere('shipping_id', $shippingID);
    }


    protected function addRowToCsvFile(\Illuminate\Support\Collection $shippingFetch, $csvFileHandler)
    {
        foreach ($shippingFetch as $key => $value) {
            if ($this->isInCsvTable($value->id)) continue;

            $tempCsvRow = [];

            $tempCsvRow[] = $value->order_id;
            $tempCsvRow[] = $value->type;
            $tempCsvRow[] = $value->tracking_number;
            $tempCsvRow[] = $value->sending_date;
            $tempCsvRow[] = $value->id;


            CreatedCsv::create(
                [
                    'shipping_id' => $value->id,
                    'csv_status' => "$this->csvFileName updated",
                    'creation_date' => date('Y-m-d H:i:s', time()),
                ]
            );

            fputcsv($csvFileHandler, $tempCsvRow, ';', ' ');

            $this->count++;
        }
    }

    protected function openCsvResource()
    {
        $this->csvFileName =  $this->option('new') ? date('Y_m_d_H_i_s', time()) . '.csv' : 'default.csv';

        if (!file_exists($this->shippingPath . $this->csvFileName)) {
            $this->csvFileResource = fopen($this->shippingPath . $this->csvFileName, 'w');
            fputcsv($this->csvFileResource, self::CSV_HEADING, ';', ' ');

            echo "$this->csvFileName file created\n\n";
        } else {
            $this->csvFileResource = fopen($this->shippingPath . $this->csvFileName, 'a');
        }
    }

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->orderPath = storage_path('app/orders/');
        $this->shippingPath = storage_path('app/shipping/');

        $this->xmlDir = opendir($this->orderPath);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->openCsvResource();

        while (($file = readdir($this->xmlDir)) !== false) {

            if (!str_contains($file, '.xml')) continue;

            $pathToXML = $this->orderPath . $file;

            $tempXML = simplexml_load_file($pathToXML);

            $tempShippingCollect = Shipping::where('order_id', $tempXML->OrderNo)->get();

            //Если в таблице нет подходяших записей - пропуск
            if (empty($tempShippingCollect->toArray())) continue;

            $this->addRowToCsvFile($tempShippingCollect, $this->csvFileResource);

            unlink($pathToXML);

            echo "$file is deleted\n";
        }

        echo "\nRecords added: $this->count";
    }
}
