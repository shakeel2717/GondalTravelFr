<div class="form-content">
    <div class="btn-box px-1">
        <div class="row g-2">
            <?php if ($booking->booking_cancellation_request == "1") { ?>
                <div class="alert alert-danger"><?= T::cancellationrequestmsg ?></div>
            <?php } ?>
            <div class="col-md-4">
                <?php if ($booking->booking_cancellation_request == "0") { ?>
                    <form onSubmit="if(!confirm('<?= T::areyousureforcancellationofthisbooking ?>')){return false;}" action="<?= root ?>flights/cancellation" method="post">
                        <input type="hidden" name="booking_no" value="<?= $booking->booking_ref_no ?>" />
                        <input type="hidden" name="booking_id" value="<?= $booking->booking_id ?>" />
                        <input type="submit" value="<?= T::requestcancellation ?>" class="btn btn-outline-danger btn-block">
                    </form>
                <?php } ?>
                <script>
                    function show_alert() {
                        if (!confirm("<?= T::thisrequestwillsubmitcancellation ?>")) {
                            return false;
                        }
                        this.form.submit();
                    }
                </script>
            </div>
            <div class="col-md-3 float-right text-right">
                <button class="btn btn-outline-success btn-block text-right" id="download"><i class="la la-print"></i> <?= T::downloadinvoice ?></button>
            </div>
        </div>
    </div>
</div>