<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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


    private const ORDER_PATH = __DIR__ . '/../../../storage/app/orders/';
    private const SHIPPING_PATH = __DIR__ . '/../../../storage/app/shipping/';

    private const CSV_HEADING = array('order_id', 'type', 'tracking_number', 'sending_date', 'shipmentNo');

    private $xmlDir;
    private $csvFileResource;
    private $csvFileName;

    private $count;

    //Проверка
    private function inCsvTable(int $shippingID): bool
    {
        $output = DB::table('created_csvs')->where('shipping_id', $shippingID)->get()->toArray();

        return !empty($output);
    }

    private function addRecordToCsvTable(int $shippingId)
    {
        DB::table('created_csvs')->insert(
            array(
                'shipping_id' => $shippingId,
                'csv_status' => "$this->csvFileName updated",
                'creation_date' => date('Y-m-d H:i:s', time()),
            )
        );
    }

    private function addRowToCsvFile(\Illuminate\Support\Collection $shippingFetch, $csvFileHandler)
    {
        foreach ($shippingFetch as $key => $value) {
            if ($this->inCsvTable($value->id)) continue;

            $tempCsvRow = [];

            $tempCsvRow[] = $value->order_id;
            $tempCsvRow[] = $value->type;
            $tempCsvRow[] = $value->tracking_number;
            $tempCsvRow[] = $value->sending_date;
            $tempCsvRow[] = $value->id;

            $this->addRecordToCsvTable($value->id);

            fputcsv($csvFileHandler, $tempCsvRow, ';', ' ');
            
            $this->count += 1;
        }
    }

    private function openCsvResource()
    {
        $this->csvFileName =  $this->option('new') ? date('Y_m_d_H_i_s', time()) . '.csv' : 'default.csv';

        if (!file_exists(self::SHIPPING_PATH . $this->csvFileName)) {
            $this->csvFileResource = fopen(self::SHIPPING_PATH . $this->csvFileName, 'w');
            fputcsv($this->csvFileResource, self::CSV_HEADING, ';', ' ');

            echo "$this->csvFileName file created\n\n";
        } else {
            $this->csvFileResource = fopen(self::SHIPPING_PATH .$this->csvFileName, 'a');
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

        $this->xmlDir = opendir(self::ORDER_PATH);

        $this->count = 0;
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

            $pathToXML = self::ORDER_PATH . $file;

            $tempXML = simplexml_load_file($pathToXML);

            $tempShippingCollect = DB::table('shipping')->where('order_id', $tempXML->OrderNo)->get();

            //Если в таблице нет подходяших записей - пропуск
            if (empty($tempShippingCollect->toArray())) continue;

            $this->addRowToCsvFile($tempShippingCollect, $this->csvFileResource);

            unlink($pathToXML);

            echo "$file is deleted\n";

            
        }

        echo "\nRecords added: $this->count";

    }
}
