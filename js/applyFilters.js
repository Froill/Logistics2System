//for driver, vehicle, start date, end date search filter in trip log, tcao
function applyFilters() {
    
    let driver = document.getElementById("filterDriver").value;
    let vehicle = document.getElementById("filterVehicle").value;
    let start = document.getElementById("filterStart").value;
    let end = document.getElementById("filterEnd").value;

    // TODO: Filter trip data by driver/vehicle/date (you can do this client-side or via AJAX)
    alert("Filtering by: driver=" + driver + ", vehicle=" + vehicle + ", range=" + start + " to " + end);
}
