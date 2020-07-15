<?php


namespace App\DataProvider;


class ProductDataProvider extends DataProviderBase
{
    private function getProduct(string $where, array $parameters)
    {
        $query = <<<SQL
                SELECT 
                    p.product_id,
                    p.product_slug,
                    p.product_vertical_id,
                    pi.productimage_id,
                    pi.productimage_order,
                    pi.productimage_width,
                    pi.productimage_height,
                    c.category_id,
                    c.category_name,
                    c.category_slug,
                    m.manufacturer_id,
                    m.manufacturer_name,
                    m.manufacturer_link,
                    m.manufacturer_image,
                    b.bestseller_text,
                    co.productcode_value,
                    pw.productcontent_description,
                    productpropertygroup_name,
                    productproperty_id,
                    productproperty_name,
                    productproperty_name_short,
                    productproperty_unit,
                    productproperty_visible,
                    productproperty_weight,
                    productpropertytext_display_text,
                    productpropertytext_tooltip,
                    product_productproperty_value,
                    product_productpropertytext_display_text,
                    product_productpropertytext_tooltip,
                    product_productpropertytext_bulletpoint,
                    productpropertyrelation_productproperty_id,
                    promotion_name,
                    promotion_start_date,
                    promotion_end_date,
                    promotion_chip_name,
                    promotion_chip_color,
                    promotion_chip_text,
                    promotion_chip_text_color,
                    promotion_tooltip_headline,
                    promotion_tooltip_text,
                    promotion_url_link,
                    promotion_url_name
                FROM
                    product p 
                LEFT JOIN
                    manufacturer m ON m.manufacturer_id = p.product_manufacturer_id    
                LEFT JOIN
                    product_category pc ON pc.product_id = p.product_id 
                LEFT JOIN
                    category c ON c.category_id = pc.category_id
                LEFT JOIN
                    productimage pi ON pi.productimage_product_id = p.product_id
                LEFT JOIN
                    bestseller b ON b.bestseller_product_id = p.product_id
                LEFT JOIN
                    productcode co ON co.productcode_product_id = p.product_id AND co.productcode_type = :ean
                LEFT JOIN
                    productcontent_wl1 pw ON pw.productcontent_product_id = p.product_id
                LEFT JOIN
                    product_productproperty ON product_productproperty_product_id = p.product_id
                LEFT JOIN
                    productpropertyrelation ON productpropertyrelation_product_productproperty_value = product_productproperty_value
                LEFT JOIN
                    productproperty ON product_productproperty_productproperty_id = productproperty_id
                LEFT JOIN
                    productpropertygroup ON productproperty_productpropertygroup_id = productpropertygroup_id
                LEFT JOIN
                    product_productpropertytext ON product_productproperty_product_productpropertytext_id = product_productpropertytext_id
                LEFT JOIN
                    productpropertytext ON productproperty_productpropertytext_id = productpropertytext_id
                LEFT JOIN
                    promotion_products ON p.product_id = promotion_products.product_id
                LEFT JOIN
                    promotion ON promotion_products.promotion_id = promotion.promotion_id  AND promotion.active = 1   
                WHERE
                    (p.product_status = :statusActive OR p.product_status = :statusInactive) AND
SQL;

        $query .= ' '.$where;

        $parameters = array_merge($parameters, [
            ':ean' => 'ean',
            ':statusActive' => 'active',
            ':statusInactive' => 'inactive',
        ]);

        $rawResult = $this->dbGenie->doFetchAllAssoc($query, $parameters);

        if (empty($rawResult)) {
            return null;
        }

        dd($rawResult);
        //return $this->map($rawResult);
    }
}