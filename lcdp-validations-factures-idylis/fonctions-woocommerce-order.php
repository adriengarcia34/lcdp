<?php
payment_complete( string $transaction_id = '' );

//________________________________________________________________________________________________
//SETTERS : Peu de chances de les utiliser pour ce projet
set_address_prop( string $prop, string $address, mixed $value );
set_order_key( string $value );
set_customer_id( integer $value );
set_billing_first_name( string $value );
set_billing_last_name( string $value );
set_billing_company( string $value );
set_billing_address_1( string $value );
set_billing_address_2( string $value );
set_billing_city( string $value );
set_billing_state( string $value );
set_billing_postcode( string $value );
set_billing_country( string $value );
maybe_set_user_billing_email( );
set_billing_email( string $value );
set_billing_phone( string $value );
set_shipping_first_name( string $value );
set_shipping_last_name( string $value );
set_shipping_company( string $value );
set_shipping_address_1( string $value );
set_shipping_address_2( string $value );
set_shipping_city( string $value );
set_shipping_state( string $value );
set_shipping_postcode( string $value );
set_shipping_country( string $value );	
set_payment_method( string $payment_method = '' );
set_payment_method_title( string $value );
set_transaction_id( string $value );
set_customer_ip_address( string $value );
set_customer_user_agent( string $value );
set_created_via( string $value );	
set_customer_note( string $value );
set_date_completed( string|integer|null $date = null );
set_date_paid( string|integer|null $date = null );
set_cart_hash( string $value );