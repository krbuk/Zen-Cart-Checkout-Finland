<?php
define('MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_TITLE', 'Verkkomaksu');
define('MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_DESCRIPTION', 'Checkout - ASETUKSET <br> Tilaa Checkout oheisella lomakkeella. Käsittelemme tilaukset yhden arkipäivän kuluessa. Luottokorttimaksujen aktivoiminen edellyttää yrityksen tietoja sekä sitä, että verkkokaupan toimitus-, palautus- ja maksuehdot ovat kunnossa<br><br>
       <a href="https://www.checkout.fi/" target="_blank">Hanki lisätietoja</a><br><br>
       <a href="https://extranet.checkout.fi/" target="_blank">Kirjaudu Checkout-tiliisi</a>');

define('MODULE_PAYMENT_CHECKOUTFINLAND_HEADER_ERROR', 'Virhe!');
define('MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_ERROR', 'Virhe Checkout -maksussa!');
define('MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_PAY_BUTTON', 'Siirry maksamaan');
define('MODULE_PAYMENT_CHECKOUTFINLAND_ALERT_TEST', 'Huomio: Test');
define('MODULE_PAYMENT_CHECKOUTFINLAND_ERROR', 'Maksu epäonnistui.');
define('MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_API_ERROR', '<b>VIRHE</b> Checkout verkkomaksu -HMAC-allekirjoituksiin väärin!');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_BODY_JSON_FAILED', '<b>VIRHE</b> Tarkista, <strong>BODY_JSON</strong> väärin!');


define('MODULE_PAYMENT_CHECKOUTFINLAND_SETTLE_TEXT', 'Veloita maksu');
define('MODULE_PAYMENT_CHECKOUTFINLAND_COUPON_TEXT', 'Kuponki');
define('MODULE_PAYMENT_CHECKOUTFINLAND_DISCOUNT_TEXT', 'Alennukset');



///
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_ACCEPTED', 'Maksu hyväksytty');
define('MODULE_PAYMENT_CHECKOUTFINLAND_ORDER_NUMBER', 'Tilausnumero');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_AUTHRORIZED', 'Maksu varmennettu.');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_SETTLED', 'Maksu veloitettu.');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_SETTLEMENT_FAILED', 'Veloitus epäonnistui. Tarkista, että alikauppiastunnus sekä salausavain on asetettu oikein ja tilaus on olemassa.');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_SETTLEMENT_JSON_FAILED', 'Veloitus epäonnistui. Tarkista, että osoite (Settlement URL) on oikein.');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_CAN_NOT_SETTLE', 'Maksua ei voitu veloittaa. Joko maksu on jo veloitettu, tai luottolaitos hylkäsi veloituspyynnön.');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_INVALID_SETTLE_REQUEST', 'Odottamaton virhe suoritettaessa veloitusta.');
define('MODULE_PAYMENT_CHECKOUTFINLAND_PAYMENT_UNKNOWN_SETTLE_RETURN', 'Odottamaton virhe suoritettaessa veloitusta.');

?>
