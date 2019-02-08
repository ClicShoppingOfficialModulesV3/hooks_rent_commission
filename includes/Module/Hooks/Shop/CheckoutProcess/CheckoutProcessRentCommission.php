<?php
  /**
 *
 *  @copyright 2008 - https://www.clicshopping.org
 *  @Brand : ClicShopping(Tm) at Inpi all right Reserved
 *  @Licence GPL 2 & MIT
 *  @licence MIT - Portion of osCommerce 2.4
 *  @Info : https://www.clicshopping.org/forum/trademark/
 *
 */

  namespace ClicShopping\OM\Module\Hooks\Shop\CheckoutProcess;

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\CLICSHOPPING;

  class CheckoutProcessRentCommission {

    public function __construct() {
      $CLICSHOPPING_Customer = Registry::get('Customer');

      if (!$CLICSHOPPING_Customer->isLoggedOn()) {
        CLICSHOPPING::redirect(null, 'Account&LogIn');
      }

      $this->commission = 0.02;
    }

    private static function LastOrderId() {
      $CLICSHOPPING_Db = Registry::get('Db');

      $QRentCommission = $CLICSHOPPING_Db->prepare('select orders_id 
                                                    from :table_orders
                                                    order by orders_id desc
                                                    limit 1
                                                   ');

      $QRentCommission->execute();
      $orders_id =  $QRentCommission->valueInt('orders_id');

      return $orders_id;
    }

    public static function RentCommission() {
      $CLICSHOPPING_Db = Registry::get('Db');

      $QRentCommission = $CLICSHOPPING_Db->prepare('select distinct ot.orders_id, 
                                                                     ot.value,
                                                                     ot.class
                                                      from :table_orders_total ot,
                                                            :table_orders_status_history ost
                                                      where ot.orders_id = ost.orders_id
                                                      and (ot.class = :class or ot.class = :class1)
                                                      and ot.orders_id = :orders_id
                                                    ');

      $QRentCommission->bindValue(':class', 'ot_subtotal');
      $QRentCommission->bindValue(':class1', 'ST');
      $QRentCommission->bindInt(':orders_id', static::LastOrderId());

      $QRentCommission->execute();

      $rent_commission = $QRentCommission->valueDecimal('value');

      return $rent_commission;
    }

    public function execute() {
      $CLICSHOPPING_Db = Registry::get('Db');

      $Qcheck = $CLICSHOPPING_Db->query('show tables like ":table_orders_sales_commission"');

      if ($Qcheck->fetch() !== false) {
        $commission = $this->commission * static::RentCommission();

        $sql_data_array = ['orders_id' => (int)static::LastOrderId(),
                           'value' => (float)$commission,
                           'date' => 'now()'
                          ];

        $CLICSHOPPING_Db->save('orders_sales_commission', $sql_data_array);

      }
    }
  }

