<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js"></script>
<style>
    .termtext {
        font-size: 10px;
        margin: 0 !important;
        line-height: 1.5;
    }

    .line {
        /* border-bottom: 2px solid black; */
        width: 70px;
        height: 5px;
    }
</style>

<?php
// dd($booking);

// IF INVOICE ID OR DATA IS WRONG
if (empty($booking)) {
    echo "<style>header{display:none;}</style><p style='display:flex;justify-content:center;height:100vh;align-items:center;'><strong>Invoice ID or Number is Wrong!</strong></p>";
    die;
}

$departure_code = "";
foreach ($routes[0] as $index => $segment) {
    $departure_code .= $segment->departure_code . '-' . $segment->arrival_code . ',';
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
                        <div class="form-title-wrap text-center">
                            <h3 class="title"><?= $departure_code ?></h3>
                        </div>
                        <div class="form-content pb-0">
                            <div class="payment-received-list">
                                <div class="mt-2 mb-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img style="filter: grayscale(100%);" src="https://gondaltravel.com/images/logo.png" width="100" alt="Logo">
                                            </div>
                                            <div class="col-md-8 text-end">
                                                <h4>PNR: 546543</h4>
                                                <h4>CONFIRMED: 546543</h4>
                                                <h3>Reservation Number:<?= $booking->booking_ref_no ?>-<?= $booking->booking_id ?></h3>
                                            </div>
                                            <hr class="mb-0 mt-0">
                                            <div class="col-md-12">
                                                <div class="passenger">
                                                    <div>
                                                        <span class="name">Passenger's Name</span>
                                                    </div>
                                                    <div class="row">
                                                        <?php
                                                        foreach ($passenger as $index => $pass) {
                                                        ?>
                                                            <div class="col-md-6">
                                                                <span style="display: inline-block; width: 20px; height: 20px; line-height: 20px; text-align: center; border-radius: 50%; background-color: #ddd; color: #000; font-weight: bold; margin-right: 10px;">
                                                                    1
                                                                </span>
                                                                <span>
                                                                    <?= $pass->title ?>

                                                                </span> <?= $pass->first_name ?> <?= $pass->last_name ?> <small style="font-size:10px;"><sup>(Adult)</sup></small>
                                                            </div>
                                                        <?php
                                                        } ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr class="mb-0 mt-0">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <?php include $invoice ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>