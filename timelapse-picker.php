<br>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xxl-8">
            <form class="text-center">
                <label class="text-center">
                    Or... pick another date:
                    <input type="date" class="form-control" name="date" min="<?php echo "$startDate"; ?>" max="<?php $date = new DateTime("now", new DateTimeZone('Pacific/Auckland') );
echo $date->format('Y-m-d');?>" value="<?php echo $_GET['date']; ?>" required>
                    <span class="validity"></span>
                </label>

                <p>
                    <button class="btn btn-secondary text-center">Submit</button>
                </p>
            </form>
        </div>
    </div>
</div>