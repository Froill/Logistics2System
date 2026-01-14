// Delete POI logic
                    document.addEventListener('DOMContentLoaded', function() {
                        const deletePoiBtn = document.getElementById('deletePoiBtn');
                        const deletePoiModal = document.getElementById('deletePoiModal');
                        const poiListContainer = document.getElementById('poiListContainer');
                        if (deletePoiBtn && deletePoiModal && poiListContainer) {
                            deletePoiBtn.onclick = function() {
                                // Load POIs and show modal
                                fetch('js/custom_pois.json?v=' + Date.now())
                                    .then(res => res.json())
                                    .then(data => {
                                        if (!Array.isArray(data) || data.length === 0) {
                                            poiListContainer.innerHTML = '<div>No POIs found.</div>';
                                            return;
                                        }
                                        let html = '<ul class="list-disc pl-4">';
                                        data.forEach((poi, idx) => {
                                            html += `<li class="flex items-center justify-between mb-2"><span><b>${poi.name}</b> (${poi.lat}, ${poi.lon})</span> <button class="btn btn-xs btn-error" data-poi-idx="${idx}"><i data-lucide="trash"></i> Delete</button></li>`;
                                        });
                                        html += '</ul>';
                                        poiListContainer.innerHTML = html;
                                        // Attach delete handlers
                                        Array.from(poiListContainer.querySelectorAll('button[data-poi-idx]')).forEach(btn => {
                                            btn.onclick = function(e) {
                                                e.preventDefault();
                                                const idx = parseInt(btn.getAttribute('data-poi-idx'));
                                                if (!confirm('Delete this POI?')) return;
                                                // Send only the POI index to backend for deletion
                                                fetch('includes/ajax.php?delete_custom_poi=1', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json'
                                                        },
                                                        body: JSON.stringify({
                                                            idx,
                                                            name: data[idx]?.name
                                                        })
                                                    })
                                                    .then(res => res.json())
                                                    .then(resp => {
                                                        if (resp.success) {
                                                            btn.parentElement.remove();
                                                            if (typeof fetchAndShowPOIs === 'function') fetchAndShowPOIs();
                                                            alert('POI deleted!');
                                                        } else {
                                                            alert('Failed to delete POI.');
                                                        }
                                                    })
                                                    .catch(() => alert('Failed to delete POI...'));
                                            };
                                        });
                                    });
                                deletePoiModal.showModal();
                            };
                        }
                    });