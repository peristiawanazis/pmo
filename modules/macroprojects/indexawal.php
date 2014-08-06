<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

require_once DP_BASE_DIR.'/modules/macroprojects/projectstats.php';
GLOBAL $m, $project_id;

$stats = array();
$pStats = array();

$stats = getTasksStats($prj);
$pStats = getProjectsStats($prj);



echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jquery.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jquery.jqplot.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jqplot.barRenderer.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jqplot.categoryAxisRenderer.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jqplot.pointLabels.min.js\"></script>\n";
//echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/excanvas.min.js\"></script>\n";
echo "<!--[if lt IE 9]><script language=\"javascript\" type=\"text/javascript\" src=\"{$base}modules/$m/js/excanvas.min.js\"></script><![endif]-->";
?>

<script class="code" type="text/javascript">
$(document).ready(function(){
		$.jqplot.config.enablePlugins = true;
        var s1 = [2, 6, 7, 10];
        var ticks = ['Completion Rate', 'Progress Rate', 'On Going', 'Delayed' ];
        var projects = <?php echo json_encode($pStats)?> ;
		var tasks = <?php echo json_encode($stats)?> ;
		var baseUrl = <?php echo "\"{$base}\"" ?> ;
		var s2 = new Array();
		var s3 = new Array();
		var ticks2 = new Array();
		var barColors = new Array();
		var x;
		var urlSet = new Array();
		s2 = [parseInt(projects.p_completed_toactive),parseInt(projects.completion_percentage),parseInt(projects.started),parseInt(projects.p_onschedule)];
		s3 = [(100-parseInt(projects.p_completed_toactive)),(100-parseInt(projects.completion_percentage)),parseInt(projects.not_started),(100-parseInt(projects.p_onschedule))];
		
		
		s4 = [parseInt(tasks.p_completed),parseInt(tasks.completion_percentage),parseInt(tasks.started),parseInt(tasks.p_onschedule)];
		s5 = [(100-parseInt(tasks.p_completed)),(100-parseInt(tasks.completion_percentage)),parseInt(tasks.not_started),(100-parseInt(tasks.p_onschedule))];
		s6 = [(100-parseInt(tasks.p_completed))];
		
        plot1 = $.jqplot('chart1', [s2,s3], {
            // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
            animate: !$.jqplot.use_excanvas,
		
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				shadowAngle: 135,
				rendererOptions: {
					barDirection: 'horizontal'
				},
				pointLabels: {show: true, formatString: '%d'}
			},
            axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks
                },
				xaxis:{
					
					tickOptions:{formatString:'%d'}
				}
            },
			title: {
				text : 'Summary Projects',
				show : true,
				fontSize : 16,
			},
            highlighter: { show: true }
        });
		$('#chart1 .jqplot-point-0.jqplot-series-0').text( $('#chart1 .jqplot-point-0.jqplot-series-0').text() + '\% completed');
		$('#chart1 .jqplot-point-0.jqplot-series-1').text( $('#chart1 .jqplot-point-0.jqplot-series-1').text() + '\% remaining');
		$('#chart1 .jqplot-point-1.jqplot-series-0').text( $('#chart1 .jqplot-point-1.jqplot-series-0').text() + '\% completed');
		$('#chart1 .jqplot-point-1.jqplot-series-1').text( $('#chart1 .jqplot-point-1.jqplot-series-1').text() + '\% remaining');
		$('#chart1 .jqplot-point-2.jqplot-series-0').text( $('#chart1 .jqplot-point-2.jqplot-series-0').text() + ' projects started');
		$('#chart1 .jqplot-point-2.jqplot-series-1').text( $('#chart1 .jqplot-point-2.jqplot-series-1').text() + ' projects not started');
		$('#chart1 .jqplot-point-3.jqplot-series-0').text( $('#chart1 .jqplot-point-3.jqplot-series-0').text() + '\% ontime');
		$('#chart1 .jqplot-point-3.jqplot-series-1').text( $('#chart1 .jqplot-point-3.jqplot-series-1').text() + '\% delayed');
		
		
		plot1 = $.jqplot('chart2', [s4,s5], {
            // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
            animate: !$.jqplot.use_excanvas,
		
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				shadowAngle: 135,
				rendererOptions: {
					barDirection: 'horizontal'
				},
				pointLabels: {show: true, formatString: '%d'}
			},
            axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks
                },
				xaxis:{
					
					tickOptions:{formatString:'%d'}
				}
            },
			title: {
				text : 'Summary Tasks',
				show : true,
				fontSize : 16,
			},
            highlighter: { show: true }
        });
		
			$('#chart2 .jqplot-point-0.jqplot-series-0').text( $('#chart2 .jqplot-point-0.jqplot-series-0').text() + '\% completed');
			$('#chart2 .jqplot-point-0.jqplot-series-1').text( $('#chart2 .jqplot-point-0.jqplot-series-1').text() + '\% remaining');
			$('#chart2 .jqplot-point-1.jqplot-series-0').text( $('#chart2 .jqplot-point-1.jqplot-series-0').text() + '\% completed');
			$('#chart2 .jqplot-point-1.jqplot-series-1').text( $('#chart2 .jqplot-point-1.jqplot-series-1').text() + '\% remaining');
			$('#chart2 .jqplot-point-2.jqplot-series-0').text( $('#chart2 .jqplot-point-2.jqplot-series-0').text() + ' tasks started');
			$('#chart2 .jqplot-point-2.jqplot-series-1').text( $('#chart2 .jqplot-point-2.jqplot-series-1').text() + ' tasks not started');
			$('#chart2 .jqplot-point-3.jqplot-series-0').text( $('#chart2 .jqplot-point-3.jqplot-series-0').text() + '\% ontime');
			$('#chart2 .jqplot-point-3.jqplot-series-1').text( $('#chart2 .jqplot-point-3.jqplot-series-1').text() + '\% delayed');
			
     /*
        $('#chart1').bind('jqplotDataClick',
            function (ev, seriesIndex, pointIndex, data) {
                location.href = urlSet[pointIndex];
            }
        );
     
		$(".jqplot-yaxis-tick").css({
			cursor: "pointer",
			zIndex: "1"
		}).click(function(){ });
		*/ 


		plot1 = $.jqplot('chart3', [s6], {
            // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
                seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				shadowAngle: 135,
				rendererOptions: {
					barDirection: 'horizontal'

				},
				pointLabels: {show: true, formatString: '%d'}
			},
            axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    
                },
				xaxis:{
					
					tickOptions:{formatString:'%d'}
				}
            },
			title: {
				text : ',',
				show : true,
				fontSize : 16,
			},
            highlighter: { show: true }
        });

        $('#chart3 .jqplot-point-0.jqplot-series-0')

    });
</script>
<script src="http://localhost:8080/pmo/modules/macroprojects/css/cssku.css\"></script>
<h1 style="margin-left: 45%;">
	IPC Summary Application  
</h1>
<table style="margin-left: 20%;">
<tr>
<td><div id="chart1" style="margin-top:20px; margin-left:20px; width:410px; height:300px;"></div></td>
<td><div id="chart2" style="margin-top:20px; margin-left:20px; width:400px; height:300px;"></div></td>
</table>

<div class="div_icon_kita" style="margin-left: 30%; ">
	<table class="icon_kita" style="margin: 30px; ">
	<td style="width: 100px;"><img src="/pmo/modules/macroprojects/images/nonpetikemas.png" style="margin-left: 10px; height: 50px; width: 50px;">
	<br><a href="?m=companies&amp;a=view&amp;company_id=1">Non Petikemas</a></br>

	<td style="width: 80px;"><img src="/pmo/modules/macroprojects/images/petikemas.png" style="height: 50px; width: 50px;">
	<br><a href="?m=companies&amp;a=view&amp;company_id=2">Petikemas</a></br>
	
	<td style="width: 120px;"><img src="/pmo/modules/macroprojects/images/kapalpemanduan.png" style="margin-left: 20px; height: 50px; width: 50px;">
	<br><a href="?m=companies&amp;a=view&amp;company_id=3">Kapal Pemanduan</a></br>
	
	<td style="width: 125px;"><img src="/pmo/modules/macroprojects/images/managementrisk.png" style="margin-left: 25px; height: 50px; width: 50px;">
	<br><a href="?m=companies&amp;a=view&amp;company_id=4">Management Resiko</a></br>

	<td style="width: 140px;"><img src="/pmo/modules/macroprojects/images/birosisfo.png" style="margin-left: 30px; height: 50px; width: 50px;">
	<br><a href="?m=companies&amp;a=view&amp;company_id=5">Biro Sistem Informasi </a></br>
	</table>


</div>	
