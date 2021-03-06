@extends('layout.main')

@section('content')
    <a href="{{ route('dash') }}" target="_BLANK" class="pull-right"><i class="fa fa-link"></i> Shareable Link</a>
    <h1>Dashboard</h1>


        <div class="card">
            <div class="card-header">Cluster Resources</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-12 text-center">
                        <h2>CPU</h2>

                        <div class="chart" id="cpuchart" data-percent="0">
                    <span class="percent">0
                    </span>
                        </div>
                        <div>
                            <span id="cpuused">0</span>% of <span id="cputotal"></span> CPU(s)
                        </div>

                    </div>
                    <div class="col-lg-3 col-12 text-center">
                        <h2>Memory</h2>

                        <div class="chart" id="memorychart" data-percent="0">
                    <span class="percent">
                        0
                    </span>
                        </div>
                        <div>
                            <span id="memoryused">0</span>% of <span id="memorytotal"></span>GB
                        </div>

                    </div>
                    <div class="col-lg-3 col-12 text-center">
                        <h2>Disk</h2>

                        <div class="chart" id="diskchart" data-percent="0">
                    <span class="percent">
                        0
                    </span>
                        </div>
                        <div>
                            <span id="diskused">0</span>% of <span id="disktotal">0</span> GB
                        </div>
                    </div>

                    <div class="col-lg-3 col-12 text-center">
                        <h2>HA Status</h2>

                        <div id="quorum_success">
                            <i class="fa fa-5x fa-check-circle text-success"></i>
                            <div>
                                Quorum OK
                            </div>
                        </div>

                        <div id="quorum_failed" style="display: none">
                            <i class="fa fa-5x fa-exclamation-circle text-danger"></i>
                            <div>
                                Quorum FAILED
                            </div>
                        </div>


                    </div>

                </div>

            </div>
        </div>


        <style>
            .chart {
                position: relative;
                display: inline-block;
                width: 110px;
                height: 110px;
                margin-top: 0px;
                margin-bottom: 0px;
                text-align: center;
            }

            .chart canvas {
                position: absolute;
                top: 0;
                left: 0;
            }

            .percent {
                display: inline-block;
                line-height: 110px;
                font-size: 22px;
                z-index: 2;
            }

            .percent:after {
                content: '%';
                margin-left: 0;
                font-size: 22px;
            }
        </style>

        <script type="text/javascript">
            window.onload = function () {


                getData();

                window.setInterval(getData, 2000);

            };

            function getData() {

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    cache: false,
                    "url": '{{ route('dashboardData') }}',
                    success: function (data) {
                        initEasyPie();
                        updateDashboard(data);
                    }
                });
            }

            function updateDashboard(data) {

                cpuused = data.status.cpu.used;
                cputotal = data.status.cpu.total;

                $('#cpuchart').data('easyPieChart').update(cpuused);
                $('#cpuchart').attr("data-percent", cpuused);
                $('#cpuchart .percent').html(cpuused);
                $('#cpuused').html(cpuused);
                $('#cputotal').html(cputotal);


                memused = data.status.memory.used;
                memtotal = data.status.memory.total;

                $('#memorychart').data('easyPieChart').update(memused);
                $('#memorychart').attr("data-percent", memused);
                $('#memorychart .percent').html(memused);
                $('#memoryused').html(memused);
                $('#memorytotal').html(memtotal);

                diskused = data.status.disk.used;
                disktotal = data.status.disk.total;


                $('#diskchart').data('easyPieChart').update(diskused);
                $('#diskchart').attr("data-percent", diskused);
                $('#diskchart .percent').html(diskused);
                $('#diskused').html(diskused);
                $('#disktotal').html(disktotal);

                if (data.status.quorum == true) {
                    $("#quorum_success").show();
                    $("#quorum_failed").hide();
                } else {
                    $("#quorum_success").hide();
                    $("#quorum_failed").show();
                }

                $('#status_vm_running').html(data.status.vms.running);
                $('#status_vm_paused').html(data.status.vms.paused);
                $('#status_vm_stopped').html(data.status.vms.stopped);

                $('#status_online').html(data.status.online);
                $('#status_offline').html(data.status.offline);

                $('#totalvms').html(data.totalvms);

                updateRecommendations(data);
                updateMapRecommendations(data);

                updateNodes(data);


            }

            function updateNodes(data) {

                html = '';
                var rowClass;
                for (node in data.nodes) {
                    mynode = data.nodes[node];

                    if (mynode.load == 0) {
                        rowclass = "bg-danger";
                    } else {
                        rowclass = "";
                    }
                    if(mynode.maintenanceMode === true)
                    {
                        maint = "<i class='fa fa-exclamation-triangle text-danger'></i>";
                    } else {
                        maint = '';
                    }

                    html += '<tr class="' + rowclass + '"><td><i class="fa fa-server"></i> ' + mynode.name + ' ' + maint +'</td> \
            <td align="right">' + mynode.load + '%</td> \
            <td align="right">' + (mynode.memory * 100).toFixed(2) + '%</td> \
            <td align="right">' + mynode.vmcount + '</td> \
            <td align="right">' + mynode.balancestatus + '</td> \
            </tr>';

                }

                $("#nodestbody").html(html);

            }

            function updateRecommendations(data) {

                if(data.migrating === true) {
                    $("#migrateCard").show();
                    $("#recommendCard").hide();
                } else {
                    $("#migrateCard").hide();
                    $("#recommendCard").show();
                }

                if(data.recommendations.length == 0) {
                    $('#recommendsButton').attr("disabled", "disabled");
                } else {
                    $('#recommendsButton').removeAttr("disabled");
                }

                html = "<ul>";
                for (id in data.recommendations) {
                    html += "<li>" + data.recommendations[id] + "</li>";
                }

                html += "</ul>"

                $("#recommendations").html(html);

                recommendJSON = JSON.stringify(data.recommendations);
                $("#recommendationsjson").val(recommendJSON);

            }

            function updateMapRecommendations(data) {

                if(data.maprecommendations.length == 0) {
                    $('#failureButton').attr("disabled", "disabled");
                } else {
                    $('#failureButton').removeAttr("disabled");
                }

                html = "<ul>";
                for (id in data.maprecommendations) {
                    html += "<li>" + data.maprecommendations[id] + "</li>";
                }

                html += "</ul>"

                $("#maprecommendations").html(html);

                recommendJSON = JSON.stringify(data.maprecommendations);
                $("#maprecommendationsjson").val(recommendJSON);

            }

            function initEasyPie() {
                $('#cpuchart').easyPieChart({
                    //your configuration goes here
                    animate: 900,
                    barColor: 'green',
                    trackColor: '#CCC',
                    lineWidth: 10
                });


                $('#memorychart').easyPieChart({
                    //your configuration goes here
                    animate: 900,
                    barColor: 'blue',
                    trackColor: '#CCC',
                    lineWidth: 10
                });

                $('#diskchart').easyPieChart({
                    //your configuration goes here
                    animate: 900,
                    barColor: 'red',
                    trackColor: '#CCC',
                    lineWidth: 10
                });
            }

        </script>


        <div class="card mt-2">
            <div class="card-header  bg-warning">Guests and Cluster</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 text-center">
                        <h3>Virtual Machines</h3>

                        <table class="text-left table table-sm">
                            <tbody>
                            <tr>
                                <td>
                                    <i class="fa fa-play-circle text-success"></i> Running
                                </td>
                                <td>
                                    <span id="status_vm_running">0</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="fa fa-play-circle text-danger"></i> Paused
                                </td>
                                <td>
                                    <span id="status_vm_paused">0</span>
                                </td>
                            </tr>
                            <tr>
                                <td valign="left">
                                    <i class="fa fa-play-circle text-muted"></i> Stopped
                                </td>
                                <td>
                                    <span id="status_vm_stopped">0</span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>

                    <div class="col-lg-6">
                        <h3>Node Status</h3>
                        <table class="table table-sm">
                            <tbody>
                            <tr>
                                <td>
                                    <i class="fa fa-check-circle text-success"></i> Online
                                </td>
                                <td><span id="status_online"></span></td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="fa fa-exclamation-triangle text-danger"></i> Offline
                                </td>
                                <td><span id="status_offline"></span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mt-2">
                <div class="card-header bg-success">Cluster Nodes</div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th align="right">CPU Load</th>
                            <th align="right">Memory Usage</th>
                            <th align="right">Started VMs</th>
                            <th align="right">Load Level</th>
                        </tr>
                        </thead>
                        <tbody id="nodestbody">

                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3"></td>
                            <td align="right"><i class="fa fa-tv"></i> <strong><span id="totalvms"></span></strong></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mt-2" id="migrateCard" style="display: none">
                <div class="card-header bg-danger">Migrating</div>
                <div class="card-body text-center">
                    <i class="fa fa-spin fa-spinner fa-4x"></i>
                    <br/>Currently migrating
                </div>
            </div>
            <div class="card mt-2" id="recommendCard" style="display: none">
                <div class="card-header bg-info">Recommendations</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <h3>Performance recommendations</h3>
                            <p id="recommendations">

                            </p>
                            {!! Form::open(array('route' => 'dorecommendations')) !!}
                            <input type="hidden" id=recommendationsjson name="recommendations" value="">
                            <button id='recommendsButton' class='btn btn-primary' type="submit">Do Recommendations</button>
                            {!! Form::close() !!}
                        </div>
                        <div class="col-lg-6">
                            <h3>Failure Domain recommendations</h3>
                            <p id="maprecommendations">

                            </p>

                            {!! Form::open(array('route' => 'map/dorecommendations')) !!}
                            <input type="hidden" id=maprecommendationsjson name="maprecommendations" value="">
                            <button id='failureButton' class='btn btn-primary' type="submit">Do Failure Domain Recommendations</button>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




@stop