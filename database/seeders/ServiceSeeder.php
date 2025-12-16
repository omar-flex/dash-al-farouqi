<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name_en' => 'Dry WH Charges', 'name_ar' => 'تخزين مواد جافة', 'internal_code' => '11001', 'service_type_id' => 1],
            ['name_en' => 'Ambient WH Charges', 'name_ar' => 'تخزين مواد مبردة', 'internal_code' => '11002', 'service_type_id' => 1],
            ['name_en' => 'Temperature Control Room', 'name_ar' => 'غرفة تبريد', 'internal_code' => '11003', 'service_type_id' => 1],
            ['name_en' => 'OPEN AREA Charges', 'name_ar' => 'تخزين ساحات مفتوحة', 'internal_code' => '11004', 'service_type_id' => 1],
            ['name_en' => 'Loading Service', 'name_ar' => 'تحميل', 'internal_code' => '11005', 'service_type_id' => 1],
            ['name_en' => 'Offloading Service', 'name_ar' => 'تنزيل', 'internal_code' => '11006', 'service_type_id' => 1],
            ['name_en' => 'Extra Handle', 'name_ar' => 'خدمات مناولة - كرتون', 'internal_code' => '11011', 'service_type_id' => 1],
            ['name_en' => 'Insurance', 'name_ar' => 'تأمين', 'internal_code' => '11009', 'service_type_id' => 1],
            ['name_en' => 'Documentation Fees', 'name_ar' => 'رسوم توثيق معاملات جمركية', 'internal_code' => '11013', 'service_type_id' => 1],


            ['name_en' => 'Pallet Charges', 'name_ar' => 'مبيعات طبالي', 'internal_code' => '11007', 'service_type_id' => 2],
            ['name_en' => 'Shrink Wrap Charges', 'name_ar' => 'تغليف طبالي', 'internal_code' => '11008', 'service_type_id' => 2],

            ['name_en' => 'Pallet Rebuilding Service', 'name_ar' => 'اعادة بناء طبلية', 'internal_code' => '11010', 'service_type_id' => 3],
            ['name_en' => 'Inventory Management Service', 'name_ar' => 'ادارة مخزون', 'internal_code' => '11012', 'service_type_id' => 3],
            ['name_en' => 'Handling Manifest Declaration', 'name_ar' => 'رسم مناولة بيانات جمركية', 'internal_code' => '11014', 'service_type_id' => 3],
            ['name_en' => 'Transportation and Distribution Service', 'name_ar' => 'توصيل الى وجهه', 'internal_code' => '11015', 'service_type_id' => 3],
            ['name_en' => 'Labor Charges', 'name_ar' => 'عامل', 'internal_code' => '11016', 'service_type_id' => 3],
            ['name_en' => 'Forklift Charges', 'name_ar' => 'استخدام رافعة شوكية', 'internal_code' => '11017', 'service_type_id' => 3],
            ['name_en' => 'Over Time Charges', 'name_ar' => 'عمل خارج اوقات العمل الرسمي', 'internal_code' => '11018', 'service_type_id' => 3],
            ['name_en' => 'Clearance Fees', 'name_ar' => 'رسم تخليص', 'internal_code' => '11019', 'service_type_id' => 3],
            ['name_en' => 'Freight Forwarding Fees', 'name_ar' => 'رسم شحن', 'internal_code' => '11020', 'service_type_id' => 3],
            ['name_en' => 'Documents Printing Charges', 'name_ar' => 'طباعة مستندات', 'internal_code' => '11021', 'service_type_id' => 3],
            ['name_en' => 'Housekeeping Charges', 'name_ar' => 'خدمات تنظيف', 'internal_code' => '11022', 'service_type_id' => 3],
            ['name_en' => 'Maintenance and Repair Service', 'name_ar' => 'خدمات صيانة واصلاح', 'internal_code' => '11023', 'service_type_id' => 3],
            ['name_en' => 'Inspection and Quality Control Service', 'name_ar' => 'خدمة الفحص ومراقبة الجودة', 'internal_code' => '11024', 'service_type_id' => 3],
            ['name_en' => 'Sorting and Classification Service', 'name_ar' => 'خدمات فرز  وتصنيف', 'internal_code' => '11025', 'service_type_id' => 3],
            ['name_en' => 'Security Monitoring Service', 'name_ar' => 'خدمات مراقبة الكاميرات', 'internal_code' => '11026', 'service_type_id' => 3],
            ['name_en' => 'Administrative Services Fees', 'name_ar' => 'خدمات اشراف', 'internal_code' => '11027', 'service_type_id' => 3],
            ['name_en' => 'Carton Repacking Service', 'name_ar' => 'اعادة تعبئة كرتون', 'internal_code' => '11028', 'service_type_id' => 3],
            ['name_en' => 'Pallet Tie Bundling Service', 'name_ar' => 'خدمة ربط وتجميع الطبالي', 'internal_code' => '11029', 'service_type_id' => 3],
            ['name_en' => 'Carton Labeling Service', 'name_ar' => 'خدمات ليبل كراتين', 'internal_code' => '11030', 'service_type_id' => 3],
            ['name_en' => 'Office Rental', 'name_ar' => 'ايجار مكاتب', 'internal_code' => '11031', 'service_type_id' => 3],
            ['name_en' => 'Electrical Plugging Service', 'name_ar' => 'نقاط شحن', 'internal_code' => '11032', 'service_type_id' => 3],
            ['name_en' => 'Overnight Parking', 'name_ar' => 'مبيت شاحنات', 'internal_code' => '11033', 'service_type_id' => 3],
            ['name_en' => 'Special Security Services', 'name_ar' => 'خدمات امن خاصة', 'internal_code' => '11034', 'service_type_id' => 3],
            ['name_en' => 'PDI Services', 'name_ar' => 'خدمات تجهييز سيارات', 'internal_code' => '11035', 'service_type_id' => 3],
            ['name_en' => 'Extra Inventory and Reporting Services', 'name_ar' => 'خدمات تقارير وجرد اضافية', 'internal_code' => '11036', 'service_type_id' => 3],
            ['name_en' => 'Off loading / on loading at site', 'name_ar' => 'خدمات تنيل وتحميل بالموقع', 'internal_code' => '11037', 'service_type_id' => 3],
            ['name_en' => 'Receivables Collection', 'name_ar' => 'خدمات تحصيل', 'internal_code' => '11038', 'service_type_id' => 3],
            ['name_en' => 'Storage Cages Rental', 'name_ar' => 'ايجار اقفاص', 'internal_code' => '11039', 'service_type_id' => 3],
            ['name_en' => 'Storage Pallet Rental', 'name_ar' => 'ايجار طبالي', 'internal_code' => '11040', 'service_type_id' => 3],
            ['name_en' => 'Transportation and Distribution Service - Multiple destinations', 'name_ar' => 'نقل وجهات اضافية', 'internal_code' => '11041', 'service_type_id' => 3],
            ['name_en' => 'Transportable Rumpah Rental', 'name_ar' => 'ايجار رمبة متحركة', 'internal_code' => '11042', 'service_type_id' => 3],
            ['name_en' => 'Courier Shipping Services', 'name_ar' => 'خدمات شحن البريد السريع', 'internal_code' => '11043', 'service_type_id' => 3],
            ['name_en' => 'Barcoding Services', 'name_ar' => 'خدمات باركود', 'internal_code' => '11044', 'service_type_id' => 3],
            ['name_en' => 'Merchandizing Services', 'name_ar' => 'تنسيق ارفف حسب الطلب', 'internal_code' => '11045', 'service_type_id' => 3],
            ['name_en' => 'Call Center Services', 'name_ar' => 'خدمات تواصل عن العميل', 'internal_code' => '11046', 'service_type_id' => 3],
            ['name_en' => 'Taping Services Charges', 'name_ar' => 'خدمات لاصق', 'internal_code' => '11047', 'service_type_id' => 3],
            ['name_en' => 'Operations Management 3PL Services', 'name_ar' => 'خدمات ادارة العمليات', 'internal_code' => '11048', 'service_type_id' => 3],
            ['name_en' => 'Extra Administrative Charges', 'name_ar' => 'خدمات ادارية اضافية', 'internal_code' => '11049', 'service_type_id' => 3],
            ['name_en' => 'VIN Number Readings Number', 'name_ar' => 'خدمات شف شاصيه', 'internal_code' => '11050', 'service_type_id' => 3],
            ['name_en' => 'Car Handling Services', 'name_ar' => 'مناولة سيارات (اخراج سيارة من البارك)', 'internal_code' => '11051', 'service_type_id' => 3],
            ['name_en' => 'Monthly Inbound Administrative Service', 'name_ar' => 'خدمات شهرية على الايداع (اوامر ادخال)', 'internal_code' => '11052', 'service_type_id' => 3],
            ['name_en' => 'Transaction Outbound', 'name_ar' => 'اوامر اخراج', 'internal_code' => '11053', 'service_type_id' => 3],
            ['name_en' => 'Fumigation Services', 'name_ar' => 'خدمات تبخير', 'internal_code' => '11054', 'service_type_id' => 3],
            ['name_en' => 'Sterilization Services', 'name_ar' => 'خدمات تعقيم', 'internal_code' => '11055', 'service_type_id' => 3],
            ['name_en' => 'Manual Crane Rental', 'name_ar' => 'ايجار جك يدوي', 'internal_code' => '11056', 'service_type_id' => 3],
            ['name_en' => 'Air Bags', 'name_ar' => 'مبيعات اكياس هوائية', 'internal_code' => '11057', 'service_type_id' => 3],
            ['name_en' => 'Certificate Issuance Services', 'name_ar' => 'اصدار شهادات', 'internal_code' => '11058', 'service_type_id' => 3],
            ['name_en' => 'Cardboard Dividers', 'name_ar' => 'مبيعات فواصل كرتونية', 'internal_code' => '11059', 'service_type_id' => 3],
            ['name_en' => 'Cardboard Box', 'name_ar' => 'مبيعات كرتون', 'internal_code' => '11060', 'service_type_id' => 3],
            ['name_en' => 'Cork Bag Sales', 'name_ar' => 'اكياس تغليف', 'internal_code' => '11061', 'service_type_id' => 3],
            ['name_en' => 'Waste Management Services', 'name_ar' => 'ادارة عمليات الاتلاف', 'internal_code' => '11062', 'service_type_id' => 3],
            ['name_en' => 'Gift Box Preparation Services', 'name_ar' => 'اعداد صناديق هدايا', 'internal_code' => '11063', 'service_type_id' => 3],
            ['name_en' => 'Batch Management Services', 'name_ar' => 'خدمات ادارة الحزم (متابعة باتش نمبر)', 'internal_code' => '11064', 'service_type_id' => 3],
            ['name_en' => 'Product Stow Services', 'name_ar' => 'خدمات تستيف البضائع', 'internal_code' => '11065', 'service_type_id' => 3],
            ['name_en' => 'Other Extra Services', 'name_ar' => 'خدمات اضافية اخرى', 'internal_code' => '11150', 'service_type_id' => 3],
        ];

        foreach ($rows as $row) {
            Service::updateOrCreate($row);
        }
    }
}
