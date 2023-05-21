<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js"></script>

<?php
// dd($booking);

// IF INVOICE ID OR DATA IS WRONG
if (empty($booking)) {
    echo "<style>header{display:none;}</style><p style='display:flex;justify-content:center;height:100vh;align-items:center;'><strong>Invoice ID or Number is Wrong!</strong></p>";
    die;
}

// payment status
if ($booking->booking_payment_status == "unpaid") {
    $payment_status = T::unpaid;
}
if ($booking->booking_payment_status == "paid") {
    $payment_status = T::paid;
}
if ($booking->booking_payment_status == "cancelled") {
    $payment_status = T::cancelled;
}
if ($booking->booking_payment_status == "disputed") {
    $payment_status = T::disputed;
}

// booking status
if ($booking->booking_status == "pending") {
    $booking_status = T::pending;
}
if ($booking->booking_status == "confirmed") {
    $booking_status = T::confirmed;
}
if ($booking->booking_status == "cancelled") {
    $booking_status = T::cancelled;
}
$departure_code = "";
foreach ($routes[0] as $index => $segment) {
    $departure_code .= '<span class="">' . $segment->departure_code . '<i class="la la-arrow-right"></i> ' . $segment->arrival_code . '</span>' . ',';
    $arrival_code = $segment->arrival_code;
    if ($index == 0) {
        $departure_date = date('Y-m-d', strtotime($segment->departure_date));
        $departure_time = date('H:i:s', strtotime($segment->departure_time));
    }
    $totalLines =  count($routes[0]) - 1;
    if ($index = $totalLines) {
        $arrival_date = date('Y-m-d', strtotime($segment->arrival_date));
        $arrival_time = date('H:i:s', strtotime($segment->arrival_time));
    }
}
?>

<section class="payment-area section-bg section-padding pt-4">
    <div class="container">
        <div class="row">
            <div>
                <div class="col-lg-8 mx-auto print" id="">
                    <div class="form-box payment-received-wrap mb-0">
                        <div class="form-title-wrap">
                            <h3 class="title"><?= T::bookinginvoice ?>
                                <span class="text-right" style="color:#0d6efd;font-weight:bold"><strong class="text-black mr-1"><?= T::reservationnumber ?>:</strong> <?= $booking->booking_ref_no ?>-<?= $booking->booking_id ?>
                                    <?php if (isset($booking->booking_pnr)) { ?>
                                        <?php if (!empty($booking->booking_pnr)) { ?>
                                            <strong class="text-black mr-1"> PNR :</strong> <?= $booking->booking_pnr ?>
                                        <?php } ?>
                                    <?php } ?>
                                </span>
                            </h3>
                        </div>
                        <div class="form-content pb-0">
                            <div class="payment-received-list">
                                <div class="mt-2 mb-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <img src="https://gondaltravel.com/images/logo.png" alt="Logo">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="table table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <h3>Gondal Travel</h3>
                                                                <p>89 Avenue du Groupe Manouchian</p>
                                                                <p>94400 Vitry - sur- Seine</p>
                                                                <p>Telephone: +33 187653786</p>
                                                                <p>Email: hello@gondaltravel.com</p>
                                                            </td>
                                                            <td>
                                                                <h3>Adresse de facturation</h3>
                                                                <p><?= $app->app->appname ?> </p>
                                                                <p>Email: <?= $app->app->email ?></p>
                                                                <p>Telephone: <?= $app->app->phone ?></p>
                                                                <p>Address: <br> <?= strip_tags($app->app->address) ?></p>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="table table-bordered mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <th colspan="2" class="text-center">
                                                                References
                                                            </th>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <ul>
                                                                    <li>
                                                                        <b> FACTURE : </b> GT102<?= $booking->booking_ref_no ?>
                                                                    </li>
                                                                    <li>
                                                                        <b>Commande : </b> GT204<?= $booking->booking_ref_no ?>G
                                                                    </li>
                                                                    <li>
                                                                        <b>Vendeur Conseil : </b> <?= $app->app->appname ?>
                                                                    </li>
                                                                    <li>
                                                                        <b>Code Interne : </b> <?= $booking->booking_payment_gateway ?>
                                                                    </li>
                                                                </ul>
                                                            </td>
                                                            <td>
                                                                <ul>
                                                                    <li>
                                                                        <b> Date de depart : </b> <?= $departure_date . " " . $departure_time ?>
                                                                    </li>
                                                                    <li>
                                                                        <b> Date de Retour : </b> <?= $arrival_date . " " . $arrival_time ?>
                                                                    </li>
                                                                    <li>

                                                                        <b> Pays : </b> <?= $arrival_code ?>
                                                                    </li>
                                                                    <li>
                                                                        <b> Devise : </b> <?= $booking->booking_curr_code ?>
                                                                    </li>
                                                                    <li>
                                                                        <b> PNR : </b> <?= $booking->booking_ref_no ?>
                                                                    </li>
                                                                </ul>

                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="table table-bordered mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <th>Organisme</th>
                                                            <th>Services</th>
                                                            <th>Montant</th>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:20%;">
                                                                <ul>
                                                                    <li>KUWAIT AIRWAYS KU-168 ECONOMY H</li>
                                                                </ul>
                                                            </td>
                                                            <td>
                                                                <ul>
                                                                    <li>Passager: <?= $passenger[0]->first_name . ' ' . $passenger[0]->last_name ?></li>
                                                                    <li>billet - </li>
                                                                    <li><?= $departure_code ?></li>
                                                                    <li>TICKET </li>
                                                                    <li>Taxes(*) aeriennes et surcharge carburant : 0000 EUR</li>
                                                                </ul>
                                                            </td>
                                                            <td>
                                                                <?= $booking->total_price . $booking->booking_curr_code ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-end p-1">total des prestations:</th>
                                                            <td class="p-1"><?= $booking->total_price . $booking->booking_curr_code ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-end p-1">Solde a payer: </th>
                                                            <td class="p-1">0.00 <?= $booking->booking_curr_code ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-end p-1">Le montant restant: </th>
                                                            <td class="p-1"><?= $booking->total_price . $booking->booking_curr_code ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 text-end text-right">
                                                <p>Solde a regler pour le = 21-May-2023</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="table table-bordered">
                                                    <tbody>
                                                        <tr>
                                                            <th colspan="4" class="text-center">
                                                                Recapitulatif TVA
                                                            </th>
                                                        </tr>
                                                        <tr>
                                                            <th>Taux</th>
                                                            <th>Mnt HT</th>
                                                            <th>Mnt TVA</th>
                                                            <th>Mnt TTC</th>
                                                        </tr>
                                                        <tr>
                                                            <td>0.00%</td>
                                                            <td>0.00 €</td>
                                                            <td>0.00</td>
                                                            <td>0.00 €</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <p class="text-start notice-text">(*) en cas d'annulation du transport, une partie des taxes est
                                                    eligible au remboursement</p>
                                                <p class="text-start notice-text">(*)Les conditions de changement de date, annulation ou toute autre
                                                    demande entrainera des frais supplémentaire. </p>
                                                <h5 class="mb-0">Merci d'avoir choisi GondalTravel.com</h5>
                                                <p class="notice-text">SASU GUR ELEC-Siret : 90305898000017 - Email : hello@gondaltravel.com</p>
                                                <p class="notice-text"><a href="#">www.gondaltravel.com</a></p>
                                                <p class="notice-text">Code Naf : 4778C - TVA Intracommunautair : FR 29 903058980* Adress 89 AV DU
                                                    GROUPE MANOUCHIAN 94400 VITRY-SUR-SEINE</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--  invoice view  -->
                        <?php include $invoice; ?>
                    </div>

                    <?php // CANCELLATION POLICY 
                    if (!empty($booking->cancellation_policy)) {
                        if (!empty($booking->after_booking_policy)) {
                    ?>
                            <div class="alert alert-danger p-3 mt-2" style="font-size: 14px; line-height: normal;">
                                <p><strong><?= T::cancelpolicy ?></strong></p>
                                <p> <?php if (isset($booking->cancellation_policy)) {
                                        echo $booking->cancellation_policy;
                                    } ?> </p>
                                <hr>
                                <p> <?php if (isset($booking->after_booking_policy)) {
                                        echo strip_tags($booking->after_booking_policy);
                                    } ?> </p>
                            </div>
                    <?php  }
                    } ?>

                </div><!-- end payment-card -->
            </div><!-- end col-lg-12 -->
        </div><!-- end col-lg-12 -->
    </div><!-- end container -->
</section>


<script>
    $('.payment_gateway option[data=<?= $booking->booking_payment_gateway ?>]').attr('selected', 'selected');
</script>

<script>
    $('#download').click(function() {
        html2canvas(document.getElementsByClassName("print")[0], {
            useCORS: true
        }).then(function(canvas) {
            var imgBase64 = canvas.toDataURL();
            console.log("imgBase64:", imgBase64);
            var imgURL = "data:image/" + imgBase64;
            var triggerDownload = $("<a>").attr("href", imgURL).attr("download", "<?= $booking->booking_ref_no ?>_<?= $booking->booking_id ?>" + ".jpg").appendTo("body");
            triggerDownload[0].click();
            triggerDownload.remove();
        });
    });
</script>