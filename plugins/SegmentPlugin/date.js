<script type="text/javascript">
$(function() {
    $( ".datepicker" ).datepicker({ dateFormat: "d M yy" });
});
$(function() {
    $('.autosubmit').change(function() {
        this.form.submit();
    });
});
</script>
