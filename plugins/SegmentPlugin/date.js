<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/2.2.4/flatpickr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/2.2.4/flatpickr.min.js"></script>
<script type="text/javascript">
flatpickr(".flatpickr", {
    altInput: true,
    altFormat: "d M Y"
});
$(function() {
    $('.autosubmit').change(function() {
        this.form.submit();
    });
});
</script>
