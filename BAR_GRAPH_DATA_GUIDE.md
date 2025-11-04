# Bar Graph Data Guide - Weekly Session Hours

## ✅ Current Implementation (DYNAMIC DATA)

The bar graph now displays **real-time weekly session hours** from your database, no longer static!

### Data Source
- **Table**: `computer_logs`
- **Field**: `uptime` (total session time in seconds)
- **Calculation**: Sum of all `uptime` values per day, converted to hours
- **Time Range**: Last 7 days (Mon-Sun)

### How It Works

**Backend** (`ComputerStatusDistribution.php`):
```php
// For each of the last 7 days
$weeklySessionHours = [];
for ($i = 6; $i >= 0; $i--) {
    $date = now()->subDays($i)->toDateString();
    
    // Sum all session uptimes for that day
    $totalSeconds = ComputerLog::whereDate('created_at', $date)
        ->sum('uptime');
    
    // Convert seconds to hours
    $hours = round($totalSeconds / 3600, 1);
    $weeklySessionHours[] = $hours;
}
```

**Frontend** (`Dashboard.vue`):
- Displays bar chart with actual session hours
- Calculates statistics dynamically:
  - **Total Weekly Hours**: Sum of all 7 days
  - **Peak Day**: Day with highest usage (Mon-Sun)
  - **Avg/Day**: Total hours ÷ 7
  - **Avg Session**: Average hours per active session
  - **Weekly Growth**: Compares last 3 days vs first 4 days

---

## 📊 What Each Metric Means

### 1. **Session Hours (Bar Graph)**
- **What it shows**: Total hours students spent in the lab each day
- **Data formula**: `SUM(uptime) / 3600` per day
- **Example**: If 10 students used computers for 30 minutes each on Monday = 5 hours total

### 2. **Peak Day**
- **What it shows**: Which day had the most lab usage
- **Calculation**: Day with maximum session hours
- **Use case**: Helps identify busiest lab days for resource planning

### 3. **Avg/Day**
- **What it shows**: Average daily lab usage across the week
- **Calculation**: `Total Weekly Hours ÷ 7`
- **Use case**: Baseline usage metric for capacity planning

### 4. **Avg Session**
- **What it shows**: Average session length across all active sessions
- **Calculation**: `Total Hours ÷ Number of Days with Activity`
- **Use case**: Understand typical student session duration

### 5. **Weekly Growth**
- **What it shows**: Trend of lab usage (increasing/decreasing)
- **Calculation**: `(Last 3 days avg - First 4 days avg) / First 4 days avg × 100%`
- **Display**: Green ↑ if positive, Red ↓ if negative
- **Use case**: Track engagement trends

---

## 💡 Alternative Data Suggestions for Bar Graph

If you want to show different data in the bar graph, here are excellent alternatives:

### Option 1: **Student Login Count** (Most Common)
```php
// Backend modification
$weeklyLogins = [];
for ($i = 6; $i >= 0; $i--) {
    $date = now()->subDays($i)->toDateString();
    $count = ComputerLog::whereDate('start_time', $date)->count();
    $weeklyLogins[] = $count;
}
```
**When to use**: Track student attendance/engagement

---

### Option 2: **Unique Students per Day**
```php
// Backend modification
$weeklyUniqueStudents = [];
for ($i = 6; $i >= 0; $i--) {
    $date = now()->subDays($i)->toDateString();
    $count = ComputerLog::whereDate('start_time', $date)
        ->distinct('student_id')
        ->count('student_id');
    $weeklyUniqueStudents[] = $count;
}
```
**When to use**: Monitor student diversity and reach

---

### Option 3: **Computer Utilization Rate** (%)
```php
// Backend modification
$weeklyUtilization = [];
$totalComputers = Computer::where('status', 'active')->count();

for ($i = 6; $i >= 0; $i--) {
    $date = now()->subDays($i)->toDateString();
    $activeComputers = ComputerLog::whereDate('start_time', $date)
        ->distinct('computer_id')
        ->count('computer_id');
    
    $utilizationPercent = $totalComputers > 0 
        ? round(($activeComputers / $totalComputers) * 100, 1) 
        : 0;
    
    $weeklyUtilization[] = $utilizationPercent;
}
```
**When to use**: Measure lab capacity efficiency

---

### Option 4: **Program-wise Usage** (Stacked Bars)
```php
// Backend modification - returns multiple series
$programs = ['BSIT', 'BSCS', 'ACT'];
$weeklyProgramData = [];

foreach ($programs as $program) {
    $programHours = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i)->toDateString();
        $totalSeconds = ComputerLog::whereDate('created_at', $date)
            ->where('program', $program)
            ->sum('uptime');
        $programHours[] = round($totalSeconds / 3600, 1);
    }
    $weeklyProgramData[$program] = $programHours;
}
```
**Frontend modification**:
```javascript
const barChartSeries = computed(() => [
  { name: 'BSIT', data: weeklyProgramData.BSIT },
  { name: 'BSCS', data: weeklyProgramData.BSCS },
  { name: 'ACT', data: weeklyProgramData.ACT }
]);
```
**When to use**: Compare usage across programs

---

### Option 5: **Peak Hours Distribution** (Hour-by-Hour)
```php
// Backend modification - shows busiest hours
$peakHours = [];
for ($hour = 8; $hour <= 17; $hour++) {
    $count = ComputerLog::whereRaw('HOUR(start_time) = ?', [$hour])
        ->whereDate('start_time', '>=', now()->subDays(7))
        ->count();
    $peakHours[] = $count;
}
```
**X-axis**: 8am, 9am, 10am, ..., 5pm  
**When to use**: Optimize lab staffing schedules

---

## 🎯 Recommended Configuration (Current)

**Current choice: Weekly Session Hours** is excellent for a thesis because:

✅ **Measurable Impact**: Shows actual time students spend learning  
✅ **Trend Analysis**: Weekly growth indicates system adoption  
✅ **Resource Planning**: Peak day helps optimize lab operations  
✅ **Engagement Metric**: Higher hours = more student engagement  
✅ **Easy to Explain**: Non-technical audience understands "hours of usage"

---

## 🔄 How to Switch to Different Data

If you want to change the bar graph data:

1. **Modify Backend** - `ComputerStatusDistribution.php` line 30-42
2. **Update Response** - Change `'weekly_session_hours'` key name if needed
3. **Update Store** - `statistics.js` to match new data property
4. **Update Chart Title** - `Dashboard.vue` template (line 497)
5. **Adjust Calculations** - Update `totalWeeklyHours`, `peakDay`, etc. computeds

---

## 📈 Data Quality Tips

To ensure accurate bar graph data:

1. **Ensure `uptime` is populated**: Check that student sessions record uptime values
2. **Monitor data gaps**: Empty bars indicate no lab activity that day
3. **Validate calculations**: Total hours should match actual lab operations
4. **Test edge cases**: What if no data for a day? (Currently shows 0)

---

## 🚀 Current Features

✅ **Dynamic Data**: Updates automatically from database  
✅ **7-Day Range**: Always shows last week (Mon-Sun)  
✅ **Auto-calculated Stats**: Peak day, averages, growth computed in real-time  
✅ **Responsive Design**: Works on mobile and desktop  
✅ **Visual Indicators**: Green for positive growth, animated bars  
✅ **Empty State Handling**: Shows 0 if no data available  

---

## Summary

Your bar graph now shows **real, live data from your computer_logs table**, tracking how many hours students are actually using the lab each day. This is perfect for a thesis as it demonstrates measurable impact and provides actionable insights for lab management! 🎓
