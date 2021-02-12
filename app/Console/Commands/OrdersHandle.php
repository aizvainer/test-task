<?php

namespace App\Console\Commands;

use App\Models\CreatedCsv;
use App\Models\Shipping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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
    protected $csvFilePath;

    protected const CSV_HEADING = "order_id;type;tracking_number;sending_date;shipmentNo\n";

    protected $csvFileName;

    protected $count = 0;

    //Проверка
    protected function isInCsvTable(int $shippingID): bool
    {
        return (bool)CreatedCsv::firstWhere('shipping_id', $shippingID);
    }


    protected function addRowToCsvFile(\Illuminate\Support\Collection $shippingFetch)
    {
        foreach ($shippingFetch as $key => $value) {
            if ($this->isInCsvTable($value->id)) continue;

            $tempCsvRow = '';

            $tempCsvRow .= $value->order_id.";";
            $tempCsvRow .= $value->type.";";
            $tempCsvRow .= $value->tracking_number.";";
            $tempCsvRow .= $value->sending_date.";";
            $tempCsvRow .= $value->id."\n";


            CreatedCsv::create(
                [
                    'shipping_id' => $value->id,
                    'csv_status' => true,
                ]
            );

            File::append($this->csvFilePath, $tempCsvRow);

            $this->count++;
        }
    }

    protected function createCsvFile()
    {
        $this->csvFileName =  $this->option('new') ? date('Y_m_d_H_i_s', time()) . '.csv' : 'default.csv';
        $this->csvFilePath = $this->shippingPath.$this->csvFileName;

        if (!File::exists($this->csvFilePath)) {
            File::put($this->csvFilePath,'');
            File::append($this->csvFilePath, self::CSV_HEADING);
            echo "$this->csvFileName file created\n\n";
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
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createCsvFile();

        foreach (File::files($this->orderPath) as $file) {

            if (!str_contains($file, '.xml')) continue;

            $tempXML = simplexml_load_file($file);

            $tempShippingCollect = Shipping::where('order_id', $tempXML->OrderNo)->get();

            //Если в таблице нет подходяших записей - пропуск
            if (empty($tempShippingCollect->toArray())) continue;

            $this->addRowToCsvFile($tempShippingCollect);

            File::delete($file);

            echo "$file is deleted\n";
        }

        echo "\nRecords added: $this->count";

    }
}
