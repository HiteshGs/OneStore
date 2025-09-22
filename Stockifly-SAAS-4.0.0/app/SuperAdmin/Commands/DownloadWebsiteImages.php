<?php

namespace App\SuperAdmin\Commands;

use App\Classes\Common;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DownloadWebsiteImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:superadmin-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download all images for website frontend for superadmin';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $websiteImages = [
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_onrfwiwasmjtqiezpe0v.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_r4ykpmmpmm3jw6bcyfdl.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_nq7ee8hmgiuyvzdseeu9.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_p5sbnqcicgxxnyj2zogs.jpeg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_xk2pcyjxncee3duknp26.svg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_clesobqaxv8w3xatjdpm.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_6nqk1wjfnphw5gyrc5md.svg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_jazw9qtjklv4ohh7q9fd.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_u4ung1evutmg2dwo1ufu.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_igdjafcxhacqpfmdkrwm.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_qtjysnlaub9pk6r2k4aw.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_sxskjzwysg6mmlzjjhi5.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_f6maqhagg70vfhg5bt56.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_osxd8td7yropdc1zqiab.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_4d3qmboclpmif7mishbg.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_jah8jlamrepyhftea6gl.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_qgny179no4wq6hfmhmgs.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_ezyntikwghro0phnzacj.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_pnomguirqhunrq9hn7o2.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_ir3byphfcg6u0yq9yotm.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_roviquvyjyprsjg9vhsw.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_dgzeoaztuacepf2cnz7r.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_umy0jlxqfhcxjd2j6uek.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_x1q094qrxffantpaqfwt.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_d7z5w6myjjfengk0dxgw.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_sicmupgrzuaiaehrlgqv.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_ctwdrplxv2ubpyhfrno5.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_r7t5ahprkcgaqcudsyoo.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_zh3vzylsdy1bfvcmlrvf.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_umglm6u0pifn4djq1z0e.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_yclshvui5dn2wmq2lidu.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_zt0jcb4tkeqxaklrqob4.png',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_rrydgq523mum7ii2u4of.webp',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_4hooep1ngw7ezbvyxmyw.webp',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_qx3ee00qpexlljrersp4.webp',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_wkrtqwt6xndjyqyn1hwe.svg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_qpqj0ndkodnlkwschfhr.svg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_wte2yems7ahxl2bqnmmx.svg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_x16mxi6xbpfbqinkv2pq.svg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_7dxcjzr4drnjti3fuxzt.svg',
            'https://stockifly-data.s3.ap-south-1.amazonaws.com/images/website_r4ykpmmpmm3jw6bcyfdl.png',
        ];

        if (config('filesystems.default') == 'local') {
            foreach ($websiteImages as $websiteImage) {
                $fileNameArray = explode('/', $websiteImage);
                $fileName = end($fileNameArray);

                if ($fileName) {
                    $folderPath = Common::getFolderPath('websiteImagePath');
                    $fileExists = Common::checkFileExists('websiteImagePath', $fileName);

                    if (!$fileExists) {
                        $contents = file_get_contents($websiteImage);
                        Storage::put($folderPath . '/' . $fileName, $contents);
                    }
                }
            }
        }
    }
}
