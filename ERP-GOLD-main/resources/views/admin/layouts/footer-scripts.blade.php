<!-- Back-to-top -->
<a href="#top" id="back-to-top"><i class="las la-angle-double-up"></i></a>
<!-- JQuery min js -->
<script src="{{URL::asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- Bootstrap Bundle js -->
<script src="{{URL::asset('assets/plugins/bootstrap/js/bootstrap.min.js')}}"></script>
<!-- Ionicons js -->
<script src="{{URL::asset('assets/plugins/ionicons/ionicons.js')}}"></script>
<!-- Moment js -->
<script src="{{URL::asset('assets/plugins/moment/moment.js')}}"></script>

<!-- Rating js-->
<script src="{{URL::asset('assets/plugins/rating/jquery.rating-stars.js')}}"></script>
<script src="{{URL::asset('assets/plugins/rating/jquery.barrating.js')}}"></script>
<script src="{{URL::asset('assets/js/eva-icons.min.js')}}"></script>
@yield('js')
<!-- Sticky js -->
<script src="{{URL::asset('assets/js/sticky.js')}}"></script>
<script src="{{URL::asset('assets/js/bootstrap-select.js')}}"></script>
<!-- custom js -->
<script src="{{URL::asset('assets/js/custom.js')}}"></script><!-- Left-menu js-->
<script src="{{URL::asset('assets/plugins/side-menu/sidemenu.js')}}"></script>
<!-- datatables js -->
<script src="{{URL::asset('assets/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.print.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.colVis.min.js')}}"></script> 
<script src="{{URL::asset('assets/plugins/select2/js/select2.full.min.js')}}"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
    $('.js-example-basic-single').select2({
        placeholder: "اختر مما يلى"
    });
    $('.progress-pie-chart').each(function () {
        var $ppc = $(this),
            percent = parseInt($ppc.data('percent')),
            deg = 360 * percent / 100;
        if (percent > 50) {
            $ppc.addClass('gt-50');
        }
        if (percent <= 25) {
            $ppc.addClass('red');
        } else if (percent >= 25 && percent <= 90) {
            $ppc.addClass('orange');
        } else if (percent >= 90) {
            $ppc.addClass('green');
        }
        $ppc.find('.ppc-progress-fill').css('transform', 'rotate(' + deg + 'deg)');
        $ppc.find('.ppc-percents span').html('<cite>' + percent + '</cite>' + '%');
    });
</script>
<script>
 
 
$(document).ready( function () {
 
            $("#example1").DataTable({
                "responsive": true, "lengthChange": true, "autoWidth": false, 
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i title="copy" class="fa fa-copy"></i>',
                    }, 
                    {
                        extend: 'excel',
                        text: '<i title="export to excel" class="fa fa-file-excel"></i>',
                    }, 
                    {
                        extend: 'print',
                        text: '<i title="print" class="fa fa-print"></i>',
                    },
                    {
                        extend: 'colvis',
                        text: '<i title="column visibility" class="fa fa-eye"></i>',
                    },  
                ],
	 
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

            $('#example2').DataTable({
                "paging": false,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": false,
                "autoWidth": false,
                "responsive": true, 
            }); 
        }); 
</script>
