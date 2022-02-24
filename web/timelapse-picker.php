<?php
$maxDate = new DateTime("now", new DateTimeZone('Pacific/Auckland'));

$maxDate1 = DateTime::createFromFormat('h:i a', $maxDate->format('h:i a'));
$maxDate2 = DateTime::createFromFormat('h:i a', '0:00 am');
$maxDate3 = DateTime::createFromFormat('h:i a', '6:05 am');

if ($maxDate1 > $maxDate2 && $maxDate1 < $maxDate3 ) {
    $maxDate->modify("-1 day");
}
?>

<br>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xxl-8">
            <form class="text-center">
                <label class="text-center">
                    Or... pick another date:
                    <input type="date" class="form-control" name="date" min="<?php echo "$startDate"; ?>"
                        max="<?php echo $maxDate->format('Y-m-d');?>" value="<?php echo $date->format('Y-m-d'); ?>"
                        required>
                    <span class="validity"></span>
                </label>

                <p>
                    <button class="btn btn-secondary text-center">Submit</button>
                </p>
            </form>
        </div>
    </div>
</div>