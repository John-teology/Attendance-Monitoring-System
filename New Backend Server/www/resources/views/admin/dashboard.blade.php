@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #800000;">Library Analytics</h2>
        <p class="text-muted mb-0">Real-time overview of library usage and attendance.</p>
    </div>
    <div class="page-actions d-flex align-items-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="min-width: 340px;">
                <span class="input-group-text bg-light border"><i class="fas fa-calendar-alt text-muted"></i></span>
                <select id="dashboardTerm" class="form-select">
                    <option value="">All Terms</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" {{ ($termId == $t->id) ? 'selected' : '' }}>
                            {{ $t->academic_year }} • {{ ucfirst($t->type) }} • {{ $t->name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary" id="clearTerm" title="Clear term filter"><i class="fas fa-times"></i></button>
            </div>
            <div id="selectedTermChip" class="d-none">
                <span class="badge bg-white text-dark border me-1">
                    <i class="fas fa-tag me-1 text-primary"></i><span id="selectedTermText"></span>
                </span>
            </div>
        </div>
        <div class="btn-group btn-group-sm">
            <a href="#" class="btn btn-outline-secondary {{ $filter == 'today' ? 'active' : '' }}" onclick="event.preventDefault(); applyDateFilter('today', this)">Today</a>
            <a href="#" class="btn btn-outline-secondary {{ $filter == 'yesterday' ? 'active' : '' }}" onclick="event.preventDefault(); applyDateFilter('yesterday', this)">Yesterday</a>
            <a href="#" class="btn btn-outline-secondary {{ $filter == 'last_7_days' ? 'active' : '' }}" onclick="event.preventDefault(); applyDateFilter('last_7_days', this)">Last 7 Days</a>
        </div>
    </div>
</div>

<!-- KPI Section -->
<div class="row g-3 mb-4">
    <!-- Current Occupancy (Gauge) -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 position-relative overflow-hidden">
                <h6 class="text-uppercase text-muted small fw-bold mb-2">Current Occupancy</h6>
                <div class="d-flex align-items-center justify-content-center" style="height: 140px; margin-top: -20px;">
                    <div id="occupancyGauge"></div>
                </div>
                <div class="text-center mt-n3">
                    <h3 class="fw-bold mb-0 text-dark">{{ $currentOccupancy }}</h3>
                    <small class="text-success fw-bold"><i class="fas fa-circle small me-1"></i> Live Users</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Visits Today (Metric + Delta) -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase text-muted small fw-bold mb-1">Visits <span id="visitsLabel">{{ $filter == 'yesterday' ? 'Yesterday' : ($filter == 'last_7_days' ? 'Last 7 Days' : 'Today') }}</span></h6>
                        <h2 class="fw-bold mb-0 text-dark" id="totalVisits">{{ number_format($totalVisitsInRange) }}</h2>
                    </div>
                    <div class="bg-light rounded p-2 text-primary border">
                        <i class="fas fa-walking fa-lg"></i>
                    </div>
                </div>
                @if($filter === 'today')
                <div class="mt-4">
                    <span class="badge bg-light text-secondary border rounded-pill">
                        <i class="fas fa-calendar-day me-1"></i> Yesterday: {{ number_format($totalVisitsPrevious ?? 0) }}
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Active Members (Sparkline) -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="text-uppercase text-muted small fw-bold mb-1">Active Members</h6>
                        <h2 class="fw-bold mb-0 text-dark">{{ number_format($activeUsers) }}</h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded p-2 text-warning">
                        <i class="fas fa-id-card fa-lg"></i>
                    </div>
                </div>
                <div id="membersSparkline" style="min-height: 50px;"></div>
                <div class="small text-muted mt-1">
                    <i class="fas fa-chart-line me-1"></i> 12-month growth trend
                </div>
            </div>
        </div>
    </div>

    <!-- Avg Duration -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase text-muted small fw-bold mb-1">Avg Visit Duration</h6>
                        <h2 class="fw-bold mb-0 text-dark">{{ $avgDurationText }}</h2>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded p-2 text-info">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: 65%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                        <span>Short</span>
                        <span>Long</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Charts Section -->
<div class="row g-4 mb-4">
    <!-- Hourly Traffic -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: #800000;">Traffic Analysis</h5>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" id="trafficFilterLabel">All Users</button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); filterTraffic('all')">All Users</a></li>
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); filterTraffic('student')">Students Only</a></li>
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); filterTraffic('faculty')">Faculty Only</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div id="trafficChart"></div>
            </div>
        </div>
    </div>

    <!-- Demographics -->
    <div class="col-lg-4">
        <div class="d-flex flex-column h-100 gap-4">
            <!-- Composition Donut -->
            <div class="card border-0 shadow-sm flex-grow-1">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h6 class="fw-bold mb-0">Member Composition</h6>
                </div>
                <div class="card-body p-3 d-flex align-items-center justify-content-center">
                    <div id="compositionChart" style="width: 100%;"></div>
                </div>
            </div>
            <!-- Year Levels Bar -->
            <div class="card border-0 shadow-sm flex-grow-1">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h6 class="fw-bold mb-0">Top Students Year Levels</h6>
                </div>
                <div class="card-body p-3">
                    <div id="yearLevelChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Section -->
<div class="row g-4 mb-4">
    <!-- Top Courses Treemap -->
    <div class="col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0" style="color: #800000;">Popular Courses</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <div id="coursesTreemap"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.applyDateFilter = function(filter, el) {
        if (el && el.parentNode) {
            var buttons = el.parentNode.querySelectorAll('.btn');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].className = buttons[i].className.replace(/\bactive\b/g, '').replace(/\s+/g, ' ').trim();
            }
            el.className = (el.className + ' active').replace(/\s+/g, ' ').trim();
        }

        var baseUrl = "{{ route('admin.dashboard') }}";
        var url = baseUrl;
        if (filter) {
            url += (baseUrl.indexOf('?') === -1 ? '?' : '&') + 'filter=' + encodeURIComponent(filter);
        }
        var term = document.getElementById('dashboardTerm') ? document.getElementById('dashboardTerm').value : '';
        if (term) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'term=' + encodeURIComponent(term);
        }

        if (window.loadSpaPage) {
            window.loadSpaPage(url);
        } else {
            window.location.href = url;
        }
    };
    window.applyTermFilter = function() {
        var baseUrl = "{{ route('admin.dashboard') }}";
        var url = baseUrl;
        var filterBtn = document.querySelector('.page-actions .btn-group .btn.active');
        var filter = filterBtn ? (filterBtn.textContent.trim().toLowerCase().replace(' ', '_')) : "{{ $filter }}";
        if (filter) {
            url += (baseUrl.indexOf('?') === -1 ? '?' : '&') + 'filter=' + encodeURIComponent(filter);
        }
        var term = document.getElementById('dashboardTerm').value;
        if (term) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'term=' + encodeURIComponent(term);
        }
        if (window.loadSpaPage) { window.loadSpaPage(url); } else { window.location.href = url; }
    };

    // Define a global function to init charts so we can call it manually if needed
    window.initDashboardCharts = function(chartData) {
        if (typeof ApexCharts === 'undefined') {
             console.error('ApexCharts is not loaded even after static include');
             return;
        }

        // Use passed data or fallback to Blade variables
        // Note: Blade variables are rendered server-side. When AJAX updates, chartData will be provided.
        // We need to store initial data in global vars to make them accessible/overwritable.
        
        var _currentOccupancy = chartData ? chartData.currentOccupancy : {{ $currentOccupancy }};
        var _hourlyTraffic = chartData ? chartData.hourlyTraffic : @json($hourlyTraffic);
        
        // Clear existing charts
        var chartIds = ["#occupancyGauge", "#membersSparkline", "#trafficChart", "#compositionChart", "#yearLevelChart", "#coursesTreemap"];
        chartIds.forEach(function(id) {
            var el = document.querySelector(id);
            if (el) el.innerHTML = '';
        });

        // 1. Current Occupancy Gauge
        var elGauge = document.querySelector("#occupancyGauge");
        if (elGauge) {
            var optionsGauge = {
                series: [Math.min((_currentOccupancy / 200) * 100, 100)],
                chart: {
                    height: 250,
                    type: 'radialBar',
                    offsetY: -20,
                    sparkline: { enabled: true }
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -90,
                        endAngle: 90,
                        track: {
                            background: "#e7e7e7",
                            strokeWidth: '97%',
                            margin: 5,
                        },
                        dataLabels: {
                            name: { show: false },
                            value: { show: false }
                        }
                    }
                },
                fill: {
                    colors: ['#800000'] // Maroon
                },
                labels: ['Occupancy'],
            };
            new ApexCharts(elGauge, optionsGauge).render();
        }

        // 2. Sparkline for Active Members
        var elSpark = document.querySelector("#membersSparkline");
        if (elSpark) {
            var optionsSpark = {
                series: [{
                    data: @json($memberGrowth)
                }],
                chart: {
                    type: 'area',
                    height: 50,
                    sparkline: { enabled: true }
                },
                stroke: { curve: 'smooth', width: 2 },
                fill: { opacity: 0.3 },
                colors: ['#ffc107'], // Gold
                tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
            };
            new ApexCharts(elSpark, optionsSpark).render();
        }

        // 3. Hourly Traffic (Stacked Area)
        var elTraffic = document.querySelector("#trafficChart");
        if (elTraffic) {
            // Parse traffic data
            var hours = Object.keys(_hourlyTraffic).map(function(h) {
                 // If h is date (2023-01-01), just use it. If h is number (7), format it.
                 if (isNaN(h)) return h; // It's a date string like "Jan 15"
                 var hour = parseInt(h);
                 return (hour > 12 ? hour - 12 + ' PM' : (hour === 12 ? '12 PM' : hour + ' AM'));
            });
            var studentData = Object.values(_hourlyTraffic).map(function(d) { return d.student; });
            var facultyData = Object.values(_hourlyTraffic).map(function(d) { return d.faculty; });
    
            var optionsTraffic = {
                series: [{
                    name: 'Students',
                    data: studentData
                }, {
                    name: 'Faculty',
                    data: facultyData
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: { show: false }
                },
                colors: ['#800000', '#ffc107'], // Maroon, Gold
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth' },
                xaxis: {
                    categories: hours,
                },
                tooltip: {
                    x: { 
                        formatter: function(val) {
                            return val;
                        }
                    },
                },
            };
            var trafficChart = new ApexCharts(elTraffic, optionsTraffic);
            trafficChart.render();
    
            // Traffic Filter Logic (Closure over chart instance)
            window.filterTraffic = function(type) {
                document.getElementById('trafficFilterLabel').innerText = type === 'all' ? 'All Users' : (type === 'student' ? 'Students Only' : 'Faculty Only');
                
                if (type === 'all') {
                    trafficChart.updateSeries([
                        { name: 'Students', data: studentData },
                        { name: 'Faculty', data: facultyData }
                    ]);
                } else if (type === 'student') {
                    trafficChart.updateSeries([
                        { name: 'Students', data: studentData },
                        { name: 'Faculty', data: [] } // Hide Faculty
                    ]);
                } else if (type === 'faculty') {
                    trafficChart.updateSeries([
                        { name: 'Students', data: [] }, // Hide Students
                        { name: 'Faculty', data: facultyData }
                    ]);
                }
            };
        }

        // 4. Member Composition Donut
        var elDonut = document.querySelector("#compositionChart");
        if (elDonut) {
            var _studentCount = chartData ? chartData.studentCount : {{ $studentCount }};
            var _facultyCount = chartData ? chartData.facultyCount : {{ $facultyCount }};
            
            // Avoid NaN errors if both are zero
            if (_studentCount === 0 && _facultyCount === 0) {
                 elDonut.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted small">No members found</div>';
            } else {
                var optionsDonut = {
                    series: [_studentCount, _facultyCount],
                    labels: ['Students', 'Faculty'],
                    chart: {
                        type: 'donut',
                        height: 220
                    },
                    colors: ['#800000', '#ffc107'],
                    dataLabels: { enabled: false },
                    legend: { position: 'bottom' },
                    plotOptions: {
                        pie: { donut: { size: '65%' } }
                    }
                };
                new ApexCharts(elDonut, optionsDonut).render();
            }
        }

        // 5. Year Level Horizontal Bar
        var elBar = document.querySelector("#yearLevelChart");
        if (elBar) {
            var yearData = @json($yearLevels);
            var optionsBar = {
                series: [{
                    data: yearData.map(function(y) { return y.total; })
                }],
                chart: {
                    type: 'bar',
                    height: 200,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        barHeight: '60%'
                    }
                },
                colors: ['#6c757d'],
                dataLabels: { enabled: true, textAnchor: 'start', style: { colors: ['#fff'] }, formatter: function (val, opt) { return val } },
                xaxis: {
                    categories: yearData.map(function(y) { return y.name; }),
                },
                grid: { show: false }
            };
            new ApexCharts(elBar, optionsBar).render();
        }

        // 6. Top Courses Bar Chart
        var elTreemap = document.querySelector("#coursesTreemap");
        if (elTreemap) {
            var courseData = @json($topCourses);
            var optionsBarChart = {
                series: [{
                    name: 'Students',
                    data: courseData.map(function(c) { return c.total; })
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false, // Vertical bars
                        columnWidth: '50%',
                    }
                },
                colors: ['#800000'], // Maroon
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: courseData.map(function(c) { return c.name; }),
                    labels: {
                        rotate: -45, // Rotate labels to fit better
                        style: { fontSize: '12px' }
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                },
                legend: {
                    show: false
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + " Students"
                        }
                    }
                }
            };
            new ApexCharts(elTreemap, optionsBarChart).render();
        }
    };

    // Call it immediately for SPA support (since DOMContentLoaded already fired)
    $(function() {
        window.initDashboardCharts();
        var termSel = document.getElementById('dashboardTerm');
        var chip = document.getElementById('selectedTermChip');
        var chipText = document.getElementById('selectedTermText');
        function updateChip() {
            if (!termSel) return;
            var opt = termSel.options[termSel.selectedIndex];
            if (opt && opt.value) {
                chipText.textContent = opt.text;
                chip.classList.remove('d-none');
            } else {
                chip.classList.add('d-none');
                chipText.textContent = '';
            }
        }
        if (termSel) {
            termSel.addEventListener('change', function(){ updateChip(); applyTermFilter(); });
            document.getElementById('clearTerm').addEventListener('click', function(){
                termSel.value = '';
                updateChip();
                applyTermFilter();
            });
            updateChip();
        }
    });
</script>
<style>
    .page-actions .btn-group .btn.active { background: #800000; color: #fff; border-color: #800000; }
    #selectedTermChip .badge { border-radius: 999px; padding: 0.35rem 0.6rem; }
    @media (max-width: 768px) {
        .page-actions .input-group { min-width: 100%; }
    }
</style>
@endsection
