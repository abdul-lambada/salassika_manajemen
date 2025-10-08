/**
 * Enhanced Charts with Mobile Responsiveness
 */

class EnhancedCharts {
    constructor() {
        this.defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: window.innerWidth < 768 ? "bottom" : "top"
                }
            }
        };
        this.colorPalette = ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b"];
    }
    
    createAttendanceTrendChart(canvasId, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;
        
        return new Chart(ctx, {
            type: "line",
            data: {
                labels: data.labels,
                datasets: [{
                    label: "Kehadiran (%)",
                    data: data.attendance_rates,
                    borderColor: this.colorPalette[0],
                    backgroundColor: this.colorPalette[0] + "20",
                    fill: true
                }]
            },
            options: this.defaultOptions
        });
    }
    
    createClassComparisonChart(canvasId, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;
        
        return new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.class_names,
                datasets: [{
                    label: "Tingkat Kehadiran (%)",
                    data: data.attendance_rates,
                    backgroundColor: this.colorPalette[0]
                }]
            },
            options: this.defaultOptions
        });
    }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    window.enhancedCharts = new EnhancedCharts();
});
