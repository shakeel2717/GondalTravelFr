<div class="row">
    <div class="col-md-12">
        <table class="table table-sm mb-0">
            <tbody>
                <tr>
                    <th>Flight</th>
                    <th>Departure</th>
                    <th></th>
                    <th>Arrival</th>
                    <th>Baggage</th>
                </tr>
                <?php
                $previousDate = null;
                foreach ($routes as $index => $route) {
                    foreach ($route as $index => $segment) { ?>
                        <?php
                        if ($index != 0 && $previousDate) {
                            $date1 = $segment->departure_date . " " . $segment->departure_time;
                            $date2 = $previousDate;

                            $dateTime1 = DateTime::createFromFormat("d-m-Y H:i", $date1);
                            $dateTime2 = DateTime::createFromFormat("d-m-Y H:i", $date2);

                            $interval = $dateTime1->diff($dateTime2);
                            $hours = $interval->h;
                            $minutes = $interval->i;
                        ?>
                            <tr>
                                <td colspan="6">
                                    <div class="text-center">
                                        <?= "Connecting Time: $hours hours $minutes minutes"; ?>
                                    </div>
                                </td>
                            </tr>

                        <?php
                        }
                        ?>
                        <tr>
                            <td class="text-center align-middle">
                                <p style="font-weight: bold;white-space: nowrap;"><?= $segment->airline_name ?></p>
                                <p style="font-weight: bold;">QR-<?= $segment->departure_flight_no ?></p>
                                <p>Economy</p>
                            </td>
                            <td class="text-center align-middle">
                                <h4><?= $segment->departure_code ?></h4>
                                <p style="white-space: nowrap;"><time class=""><?= date('d D M y', strtotime($segment->departure_date)) ?> <?= $segment->departure_time ?></time></p>
                                <p><small style="font-size: 12px;">Charles De Gaulle Terminal-1</small></p>
                            </td>
                            <td class="text-center align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="airplan">
                                        <div class="d-flex align-items-center">
                                            <i style="font-size: 2em;" class="la la-plane-departure mb-3"></i>
                                            <div class="line"></div>
                                            <i style="font-size: 2em;" class="la la-plane-arrival  mb-3"></i>
                                        </div>
                                        <p style="margin-top:-35px;font-size:13px"><?= $segment->duration_time; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <h4><?= $segment->arrival_code ?></h4>
                                <p style="white-space: nowrap;"><?= date('d D M y', strtotime($segment->arrival_date)) ?> <?= $segment->arrival_time ?></p>
                                <p><small style="font-size: 12px;">Charles De Gaulle Terminal-1</small></p>
                            </td>
                            <td class="text-center align-middle">
                                <div class="d-flex">
                                    <div class="suitecase text-center">
                                        <i style="font-size: 1.5rem;" class="la la-suitcase"></i>
                                        <p><?= $segment->luggage ?>KG</p>
                                    </div>
                                    <div class="handcarry text-center">
                                        <i style="font-size: 1.5rem;" class="la la-shopping-bag"></i>
                                        <p>1Bag</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                <?php
                        $previousDate = $segment->arrival_date . " " . $segment->arrival_time;
                    }
                }
                ?>
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
<?php

// dd($routes[0]);
// die;

if (!empty($routes[1])) {
    foreach ($routes[1] as $index => $segment) { ?>
<?php }
} ?>