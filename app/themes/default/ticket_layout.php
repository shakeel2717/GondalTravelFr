<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js"></script>
<style>
    .termtext {
        font-size: 10px;
        margin: 0 !important;
        line-height: 1.5;
    }

    .line {
        border-bottom: 2px solid black;
        width: 150px;
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
                        <div class="form-title-wrap">
                            <h3 class="title"><?= $departure_code ?>
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
                                            <div class="col-md-3">
                                                <img src="https://gondaltravel.com/images/logo.png" alt="Logo">
                                            </div>
                                            <div class="col-md-9">
                                                <div class="card mb-3" style="min-height: 150px;">
                                                    <div class="card-body d-flex align-items-center">
                                                        <?php
                                                        foreach ($passenger as $index => $pass) {
                                                        ?>
                                                            <div class="names">
                                                                <h5><small>1. <?= $pass->title ?>.</small> <?= $pass->first_name ?> sdf sdafasdf sadf <?= $pass->last_name ?> <small>(<?= $pass->traveller_type ?>)</small></h5>
                                                                <h5><small>2. <?= $pass->title ?>.</small> <?= $pass->first_name ?> <?= $pass->last_name ?> <small>(<?= $pass->traveller_type ?>)</small></h5>
                                                                <h5><small>3. <?= $pass->title ?>.</small> <?= $pass->first_name ?> sdf<?= $pass->last_name ?> <small>(<?= $pass->traveller_type ?>)</small></h5>
                                                                <h5><small>4. <?= $pass->title ?>.</small> <?= $pass->first_name ?>s adfsadf <?= $pass->last_name ?> <small>(<?= $pass->traveller_type ?>)</small></h5>
                                                                <h5><small>5. <?= $pass->title ?>.</small> <?= $pass->first_name ?>sd f <?= $pass->last_name ?> <small>(<?= $pass->traveller_type ?>)</small></h5>
                                                                <h5><small>6. <?= $pass->title ?>.</small> <?= $pass->first_name ?> sa dfsdfasa<?= $pass->last_name ?> <small>(<?= $pass->traveller_type ?>)</small></h5>
                                                            </div>
                                                        <?php
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="table table-sm mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <th>Flight</th>
                                                            <th>Date</th>
                                                            <th>Departure</th>
                                                            <th></th>
                                                            <th>Arrival</th>
                                                            <th>Baggage</th>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <p style="font-weight: bold;">QATAR AIRWAYS</p>
                                                                <p style="font-weight: bold;">QR-42</p>
                                                                <p>ECONOMY O</p>
                                                            </td>
                                                            <td class="text-center">
                                                                <p>Tuesday</p>
                                                                <p style="font-weight: bold;">30-May</p>
                                                                <p>2023</p>
                                                            </td>
                                                            <td class="text-center">
                                                                <p>
                                                                <h4>CDG</h4>
                                                                </p>
                                                                <p>09:05</p>
                                                                <p><small style="font-size: 12px;">Charles De Gaulle Terminal-1</small></p>
                                                            </td>
                                                            <td class="align-middle text-center">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="airplan">
                                                                        <div class="d-flex align-items-center">
                                                                            <i style="font-size: 2em;" class="la la-plane-departure"></i>
                                                                            <div class="line"></div>
                                                                            <i style="font-size: 2em;" class="la la-plane-arrival"></i>
                                                                        </div>
                                                                        <p>6H 30M</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <p>
                                                                <h4>DOH</h4>
                                                                </p>
                                                                <p>09:05</p>
                                                                <p><small style="font-size: 12px;">Charles De Gaulle Terminal-1</small></p>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="suitecase">
                                                                        <i style="font-size: 2em;" class="la la-suitcase"></i>
                                                                        <p>25 KG</p>
                                                                    </div>
                                                                    <div class="handcarry">
                                                                        <i style="font-size: 2em;" class="la la-briefcase"></i>
                                                                        <p>2 Bag</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <p class="text-center">Lyout Time 343 Hour</p>
                                                <hr class="mb-0 mt-0">
                                                <table class="table table-sm mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <th>Flight</th>
                                                            <th>Date</th>
                                                            <th>Departure</th>
                                                            <th></th>
                                                            <th>Arrival</th>
                                                            <th>Baggage</th>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <p style="font-weight: bold;">QATAR AIRWAYS</p>
                                                                <p style="font-weight: bold;">QR-42</p>
                                                                <p>ECONOMY O</p>
                                                            </td>
                                                            <td class="text-center">
                                                                <p>Tuesday</p>
                                                                <p style="font-weight: bold;">30-May</p>
                                                                <p>2023</p>
                                                            </td>
                                                            <td class="text-center">
                                                                <p>
                                                                <h4>CDG</h4>
                                                                </p>
                                                                <p>09:05</p>
                                                                <p><small style="font-size: 12px;">Charles De Gaulle Terminal-1</small></p>
                                                            </td>
                                                            <td class="align-middle text-center">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="airplan">
                                                                        <div class="d-flex align-items-center">
                                                                            <i style="font-size: 2em;" class="la la-plane-departure"></i>
                                                                            <div class="line"></div>
                                                                            <i style="font-size: 2em;" class="la la-plane-arrival"></i>
                                                                        </div>
                                                                        <p>6H 30M</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <p>
                                                                <h4>DOH</h4>
                                                                </p>
                                                                <p>09:05</p>
                                                                <p><small style="font-size: 12px;">Charles De Gaulle Terminal-1</small></p>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="suitecase">
                                                                        <i style="font-size: 2em;" class="la la-suitcase"></i>
                                                                        <p>25 KG</p>
                                                                    </div>
                                                                    <div class="handcarry">
                                                                        <i style="font-size: 2em;" class="la la-briefcase"></i>
                                                                        <p>2 Bag</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p class="text-center">Terms & Conditions</p>
                                                <p class="termtext">The following terms and conditions should be read carefully.</p>
                                                <p class="termtext">1)Check-in counters open 3 hours prior to flight departure and close 1 hour prior to flight departure. If you fail to report on time you may be denied boarding.</p>
                                                <p class="termtext">2)The passenger's name(s) should be checked as per their passport/identity proof, departure and arrival dates, times, flight number, terminal, and baggage information.</p>
                                                <p class="termtext">3) When traveling internationally, please confirm your travel documentation requirements with your airline or relevant Embassy, such as your passport, visa, and ok to board.</p>
                                                <p class="termtext">4)Some countries may impose local taxes (VAT, tourist tax, etc.) that must be paid locally.</p>
                                                <p class="termtext">5) We are not responsible for schedule changes or flight cancellations by the airline before or after tickets are issued.</p>
                                                <p class="termtext">6) We cannot be held liable for any loss, damage or mishap to the traveler's or his/her belongings due to accident, theft or other unforeseeable circumstances.</p>
                                                <p class="termtext">7)Booking amendments will be subject to the airline's terms and conditions, including penalties, fare difference, and availability.</p>
                                                <p class="termtext">8)A cancellation or refund of a booking will be handled on a case-by-case basis depending on the airline and agency policies.</p>
                                                <p class="termtext">9)Should you need amendments, cancellations, or ancillary services, contact your travel partner.</p>
                                                <p class="text-center">Bon Voyage!</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <p class="termtext">
                                                        CUSTOMER SERVICE <br>
                                                        FRA: 0187653786 <br>
                                                        UK: 00448007074285 <br>
                                                        USA: 0018143008040
                                                    </p>
                                                    <p>
                                                        www.gondaltravel.com
                                                    </p>
                                                    <p class="termtext">
                                                        BOOKING PARTNER<br>
                                                        MAIN OFFICE <br>
                                                        OFFICE PHONE: <br>
                                                        0033187653786 <br>
                                                        hello@gondaltravel.com
                                                    </p>
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
    </div>
</section>