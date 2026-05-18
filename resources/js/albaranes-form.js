const euroFormatter = new Intl.NumberFormat("es-ES", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const clampNumber = (value) => {
    const parsed = Number(value);
    if (!Number.isFinite(parsed) || parsed < 0) {
        return 0;
    }

    return parsed;
};

const round2 = (value) => {
    return Math.round(clampNumber(value) * 100) / 100;
};

document.addEventListener("DOMContentLoaded", () => {
    const root = document.querySelector("[data-albaran-form]");
    console.warn('[albaranes] albaranes-form.js loaded');
    if (!root) {
        return;
    }

    let pedidoMode = root.dataset.pedidoMode === "1";

    const articuloInput = document.getElementById("linea_articulo");
    const descripcionInput = document.getElementById("linea_descripcion");
    const cantidadInput = document.getElementById("linea_cantidad");
    const precioInput = document.getElementById("linea_precio");
    const medidaInput = document.getElementById("linea_medida");
    const margenInput = document.getElementById("linea_margen");
    const addButton = document.getElementById("btnAddLinea");
    const editButton = document.getElementById("btnEditLinea");
    const deleteButton = document.getElementById("btnDeleteLinea");
    const tableBody = document.getElementById("lineasBody");
    const totalElement = document.getElementById("albaranTotalValue");
    const lineasJsonInput = document.getElementById("lineasJson");
    const pedidoClienteSelect = document.getElementById("pedido_cliente");
    const clienteSelect = document.getElementById("cliente_id");
    const otInput = document.getElementById("ot");

    if (!tableBody || !totalElement || !lineasJsonInput) {
        return;
    }

    const parseLineas = (raw) => {
        if (!raw || typeof raw !== "string") {
            return [];
        }

        try {
            const decoded = JSON.parse(raw);
            if (!Array.isArray(decoded)) {
                return [];
            }

            return decoded
                .filter((linea) => linea && typeof linea === "object")
                .map((linea) => {
                    const cantidad = round2(linea.cantidad);
                    const cantidadMax = Math.max(cantidad, round2(linea.cantidad_max ?? linea.cantidad));
                    const precioUnitario = round2(linea.precio_unitario ?? linea.precio);
                    const margen = round2(linea.margen);
                    const total = round2(cantidad * precioUnitario * (1 + margen / 100));

                    return {
                        articulo_id: linea.articulo_id ?? null,
                        articulo: String(linea.articulo ?? "").trim(),
                        descripcion: String(linea.descripcion ?? "").trim(),
                        cantidad,
                        cantidad_max: cantidadMax,
                        medida: String(linea.medida ?? linea.unidad ?? "").trim(),
                        precio_unitario: precioUnitario,
                        margen,
                        total,
                        selected: pedidoMode ? linea.selected !== false : true,
                    };
                })
                .filter((linea) => linea.descripcion !== "");
        } catch (error) {
            return [];
        }
    };

    const lineasFromInput = parseLineas(lineasJsonInput.value);
    const lineasFromDataset = parseLineas(root.dataset.initialLineas ?? "[]");

    let lineas = lineasFromInput.length > 0 ? lineasFromInput : lineasFromDataset;
    let activePedidoKey = null;
    let selectedIndex = -1;

    const autosizeDescripcion = () => {
        if (!descripcionInput || descripcionInput.tagName !== "TEXTAREA") {
            return;
        }

        descripcionInput.style.height = "auto";
        descripcionInput.style.height = `${Math.min(descripcionInput.scrollHeight, 192)}px`;
    };

    const resetInputs = () => {
        if (!articuloInput || !descripcionInput || !cantidadInput || !precioInput || !medidaInput || !margenInput) {
            return;
        }

        articuloInput.value = "";
        descripcionInput.value = "";
        cantidadInput.value = "1";
        medidaInput.value = "";
        precioInput.value = "0";
        margenInput.value = "0";
        autosizeDescripcion();
        descripcionInput.focus();
    };

    const syncHiddenField = () => {
        const payload = pedidoMode
            ? lineas.filter((linea) => linea.selected !== false)
            : lineas;

        lineasJsonInput.value = JSON.stringify(payload);
    };

    const updateTotal = () => {
        const total = lineas.reduce((acc, linea) => acc + (pedidoMode && linea.selected === false ? 0 : clampNumber(linea.total)), 0);
        totalElement.textContent = `${euroFormatter.format(round2(total))} €`;
    };

    const setSideButtonsState = () => {
        const hasSelection = selectedIndex >= 0 && selectedIndex < lineas.length;

        if (editButton) {
            editButton.disabled = !hasSelection;
        }

        if (deleteButton) {
            deleteButton.disabled = !hasSelection;
        }
    };

    const resolveCantidadMax = (linea) => {
        const current = round2(linea?.cantidad ?? 0);
        const maxValue = round2(linea?.cantidad_max ?? linea?.cantidad ?? 0);

        return Math.max(current, maxValue);
    };

    const updateLineaCantidad = (index, rawValue) => {
        const linea = lineas[index];
        if (!linea) {
            return;
        }

        const maxCantidad = resolveCantidadMax(linea);
        let cantidad = round2(rawValue);

        if (Number.isFinite(maxCantidad)) {
            cantidad = Math.min(cantidad, maxCantidad);
        }

        linea.cantidad = cantidad;
        linea.total = round2(cantidad * linea.precio_unitario * (1 + linea.margen / 100));
    };

    const setPedidoMode = (enabled) => {
        pedidoMode = !!enabled;

        // Hide or show the linea input row
        const lineaInputRow = document.querySelector('.linea-input-row');
        if (lineaInputRow) {
            lineaInputRow.style.display = pedidoMode ? 'none' : '';
        }

        // Disable side add/edit/delete when in pedidoMode
        if (addButton) addButton.disabled = pedidoMode;
        if (editButton) editButton.disabled = pedidoMode || editButton.disabled;
        if (deleteButton) deleteButton.disabled = pedidoMode || deleteButton.disabled;

        // Update table header to reflect pedido mode columns
        const headerRow = document.querySelector('.lineas-table thead tr');
        if (headerRow) {
            if (pedidoMode) {
                headerRow.innerHTML = `
                    <th>Código ref.</th>
                    <th>Descripcion</th>
                    <th>Cantidad</th>
                    <th>Medida</th>
                    <th>P. unitario</th>
                    <th>Margen</th>
                    <th>Total</th>
                    <th style="width: 7rem">Incluir</th>
                `;
            } else {
                headerRow.innerHTML = `
                    <th>Linea</th>
                    <th>Código ref.</th>
                    <th>Descripcion</th>
                    <th>Cantidad</th>
                    <th>Medida</th>
                    <th>P. unitario</th>
                    <th>Margen</th>
                    <th>Total</th>
                    <th>Accion</th>
                `;
            }
        }

        // Ensure selection note exists when in pedidoMode
        if (pedidoMode) {
            let note = document.querySelector('.albaran-selection-note');
            if (!note) {
                note = document.createElement('div');
                note.className = 'albaran-selection-note';
                note.innerHTML = '<i class="fas fa-circle-info" aria-hidden="true"></i> Marca los artículos que quieres incluir en este albarán. Los no marcados quedarán fuera del documento.';
                const articulosCard = document.querySelector('.albaran-card h2');
                if (articulosCard && articulosCard.textContent && articulosCard.textContent.trim().toUpperCase().includes('ARTICULOS')) {
                    articulosCard.parentElement.insertBefore(note, articulosCard.parentElement.querySelector('.linea-input-row') || articulosCard.parentElement.querySelector('.table-responsive'));
                } else {
                    root.insertBefore(note, root.firstChild);
                }
            }
        } else {
            const note = document.querySelector('.albaran-selection-note');
            if (note && note.parentElement) note.parentElement.removeChild(note);
        }

        renderRows();
    };

    const renderRows = () => {
        if (lineas.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="${pedidoMode ? 8 : 9}" class="lineas-empty">${pedidoMode ? 'No hay artículos pendientes para incluir.' : 'No hay lineas añadidas.'}</td></tr>`;
            selectedIndex = -1;
            setSideButtonsState();
            syncHiddenField();
            updateTotal();
            return;
        }

        const rowsHtml = lineas
            .map((linea, index) => {
                const isSelected = index === selectedIndex;
                const medida = String(linea.medida ?? "").trim();
                const totalLinea = clampNumber(linea.total);
                const checked = linea.selected !== false;
                const maxCantidad = resolveCantidadMax(linea);
                const qtyValue = Number.isFinite(linea.cantidad) ? linea.cantidad : 0;

                if (pedidoMode) {
                    return `
                        <tr data-index="${index}"${checked ? ' class="is-selected"' : ' class="is-deselected"'}>
                            <td>${linea.articulo ? `<strong>${linea.articulo}</strong>` : '<span class="text-muted">-</span>'}</td>
                            <td>${linea.descripcion}</td>
                            <td>
                                <input type="number" class="albaran-line-qty" data-action="edit-cantidad" data-index="${index}" min="0" step="0.01" max="${maxCantidad}" value="${qtyValue}" ${checked ? '' : 'disabled'}>
                            </td>
                            <td>${medida ? medida : '<span class="text-muted">-</span>'}</td>
                            <td>${euroFormatter.format(linea.precio_unitario)} €</td>
                            <td>${euroFormatter.format(linea.margen)} %</td>
                            <td class="linea-total">${euroFormatter.format(totalLinea)} €</td>
                            <td>
                                <label class="albaran-line-check">
                                    <input type="checkbox" class="albaran-line-check__input" data-action="toggle-selected" data-index="${index}" ${checked ? 'checked' : ''}>
                                </label>
                            </td>
                        </tr>
                    `;
                }

                return `
                    <tr data-index="${index}"${isSelected ? ' class="is-selected"' : ""}>
                        <td>${index + 1}</td>
                        <td>${linea.articulo ? `<strong>${linea.articulo}</strong>` : '<span class="text-muted">-</span>'}</td>
                        <td>${linea.descripcion}</td>
                        <td>${euroFormatter.format(linea.cantidad)}</td>
                        <td>${medida ? medida : '<span class="text-muted">-</span>'}</td>
                        <td>${euroFormatter.format(linea.precio_unitario)} €</td>
                        <td>${euroFormatter.format(linea.margen)} %</td>
                        <td class="linea-total">${euroFormatter.format(totalLinea)} €</td>
                        <td>
                            <button type="button" class="linea-btn linea-edit" data-action="edit" data-index="${index}" title="Editar linea">
                                <i class="far fa-edit"></i>
                            </button>
                            <button type="button" class="linea-btn linea-delete" data-action="delete" data-index="${index}" title="Eliminar linea">
                                <i class="far fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
            })
            .join("");

        tableBody.innerHTML = rowsHtml;
        setSideButtonsState();
        syncHiddenField();
        updateTotal();
    };

    const saveCurrentInputs = () => {
        if (pedidoMode) {
            return;
        }

        const articulo = articuloInput.value.trim();
        const descripcion = descripcionInput.value.trim();
        const cantidad = round2(cantidadInput.value);
        const medida = medidaInput.value.trim();
        const precioUnitario = round2(precioInput.value);
        const margen = round2(margenInput.value);

        if (!descripcion || cantidad <= 0) {
            descripcionInput.focus();
            return;
        }

        const total = round2(cantidad * precioUnitario * (1 + margen / 100));

        const payload = {
            articulo_id: selectedIndex >= 0 && selectedIndex < lineas.length ? (lineas[selectedIndex].articulo_id ?? null) : null,
            articulo,
            descripcion,
            cantidad,
            medida,
            precio_unitario: precioUnitario,
            margen,
            total,
        };

        if (selectedIndex >= 0 && selectedIndex < lineas.length) {
            lineas[selectedIndex] = payload;
            selectedIndex = -1;
            addButton.innerHTML = '<i class="fas fa-plus"></i> Agregar';
        } else {
            lineas.push(payload);
        }

        resetInputs();
        renderRows();
    };

    if (addButton) {
        addButton.addEventListener("click", saveCurrentInputs);
    }

    if (!pedidoMode && cantidadInput && precioInput && margenInput) {
        [cantidadInput, precioInput, margenInput].forEach((input) => {
            input.addEventListener("keydown", (event) => {
                if (event.key === "Enter") {
                    event.preventDefault();
                    saveCurrentInputs();
                }
            });
        });
    }

    if (descripcionInput) {
        descripcionInput.addEventListener("input", autosizeDescripcion);
    }

    const syncPedidoClienteFields = () => {
        if (!pedidoClienteSelect || !clienteSelect || !otInput) {
            return;
        }

        // Handle both <select> (create) and <input> (pantalla-roja) for pedido_cliente.
        let selectedOption = null;
        const isInputField = pedidoClienteSelect.tagName === 'INPUT' || pedidoClienteSelect.tagName === 'TEXTAREA';

        if (isInputField) {
            const rawVal = String(pedidoClienteSelect.value || '').trim();
            if (rawVal === '') {
                // If empty input, do not clear existing cliente/ot values; just disable pedido mode.
                clienteSelect.value = clienteSelect.value || '';
                otInput.value = otInput.value || '';
                setPedidoMode(false);
                return;
            }

            // Create a minimal selectedOption-like object for downstream logic
            selectedOption = { value: rawVal, dataset: {} };
        } else {
            selectedOption = pedidoClienteSelect.selectedOptions && pedidoClienteSelect.selectedOptions.length > 0
                ? pedidoClienteSelect.selectedOptions[0]
                : null;
        }

        // Fallback: if Select2 is active it may not expose selectedOptions the same way
        if (!selectedOption && window.jQuery && window.jQuery.fn.select2 && window.jQuery(pedidoClienteSelect).data('select2')) {
            try {
                const sd = window.jQuery(pedidoClienteSelect).select2('data');
                if (Array.isArray(sd) && sd.length > 0) {
                    const first = sd[0];
                    if (first && first.element) {
                        selectedOption = first.element;
                    }
                }
            } catch (e) {
                // ignore
            }
        }

        console.debug('[albaranes] syncPedidoClienteFields called', { selectedOption });

        if (!selectedOption || !selectedOption.value) {
            clienteSelect.value = "";
            otInput.value = "";
            setPedidoMode(false);
            return;
        }

        let clienteId = selectedOption?.dataset?.clienteId || "";
        let ot = selectedOption?.dataset?.ot || "";
        const pedidoId = selectedOption?.dataset?.pedidoId || null;
        const pedidoKey = pedidoId || selectedOption?.value || '';

        console.debug('[albaranes] resolved clienteId, ot, pedidoId', { clienteId, ot, pedidoId });

        const applyFields = (data) => {
            if (pedidoKey !== activePedidoKey) {
                return;
            }

            const cId = data.id_cliente ?? data.id ?? clienteId ?? "";
            const cOt = data.ot ?? ot ?? "";

            if (clienteSelect) {
                if (window.jQuery && window.jQuery.fn.select2 && window.jQuery(clienteSelect).data('select2')) {
                    window.jQuery(clienteSelect).val(String(cId)).trigger('change');
                } else {
                    clienteSelect.value = String(cId);
                    clienteSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            otInput.value = cOt;

            const hasLineas = Array.isArray(data.lineas) || Array.isArray(data.lista_articulos);
            const rawLineas = Array.isArray(data.lineas)
                ? data.lineas
                : (Array.isArray(data.lista_articulos) ? data.lista_articulos : []);

            if (hasLineas) {
                if (rawLineas.length > 0) {
                    // Convert raw lineas to the internal linea shape and mark selected=true
                    lineas = rawLineas
                        .filter((l) => l && typeof l === 'object' && (l.descripcion || l.descripcion === 0))
                        .map((l) => {
                            const cantidad = round2(l.cantidad ?? 0);
                            const cantidadMax = Math.max(cantidad, round2(l.cantidad_max ?? l.cantidad ?? 0));
                            const precioUnitario = round2(l.precio_unitario ?? l.precio ?? 0);
                            const margen = round2(l.margen ?? 0);
                            const total = round2(l.total ?? (cantidad * precioUnitario * (1 + (margen / 100))));

                            return {
                                articulo_id: l.articulo_id ?? null,
                                articulo: String(l.articulo ?? '').trim(),
                                descripcion: String(l.descripcion ?? '').trim(),
                                cantidad,
                                cantidad_max: cantidadMax,
                                medida: String(l.medida ?? l.unidad ?? '').trim(),
                                precio_unitario: precioUnitario,
                                margen,
                                total,
                                selected: true,
                            };
                        });
                } else {
                    lineas = [];
                }

                renderRows();
            }
        };

        activePedidoKey = pedidoKey;
        lineas = [];
        setPedidoMode(true);

        // Apply cliente/ot from option immediately
        if (clienteId || ot) {
            applyFields({ id_cliente: clienteId, ot });
        }

        // Otherwise try to fetch via AJAX using pedido_id or numero
        const params = pedidoId ? `?pedido_id=${encodeURIComponent(pedidoId)}` : `?numero=${encodeURIComponent(selectedOption?.value || '')}`;
        fetch(`/pedidos-clientes/data${params}`, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then((resp) => {
                if (!resp.ok) throw resp;
                return resp.json();
            })
            .then((data) => {
                applyFields(data || {});
            })
            .catch((err) => {
                console.debug('[albaranes] pedido data fetch failed', err);
            });
    };

    if (pedidoClienteSelect && window.jQuery && typeof window.jQuery.fn.select2 === "function") {
        const $pc = window.jQuery(pedidoClienteSelect);
        $pc.select2({
            theme: "bootstrap4",
            width: "100%",
            placeholder: pedidoClienteSelect.dataset.placeholder || "Selecciona pedido...",
            allowClear: true,
            minimumResultsForSearch: 0,
        });

        $pc.on('select2:select select2:unselect select2:clear', syncPedidoClienteFields);
        $pc.on('change', syncPedidoClienteFields);

        // Trigger once to populate initial values from a preselected option
        $pc.trigger('change');
    }

    if (pedidoClienteSelect) {
        pedidoClienteSelect.addEventListener("change", syncPedidoClienteFields);
        syncPedidoClienteFields();
    }

    tableBody.addEventListener("click", (event) => {
        const checkboxTarget = event.target.closest('input[data-action="toggle-selected"]');
        const qtyTarget = event.target.closest('input[data-action="edit-cantidad"]');
        const target = event.target.closest("button[data-action]");
        const row = event.target.closest("tr[data-index]");

        if (pedidoMode && (checkboxTarget || qtyTarget)) {
            return;
        }

        if (row) {
            selectedIndex = Number(row.dataset.index);
        }

        if (target) {
            const index = Number(target.dataset.index);
            const action = target.dataset.action;

            if (pedidoMode && action === "toggle-selected") {
                lineas[index].selected = target.checked;
                renderRows();
                return;
            }

            if (action === "delete") {
                lineas.splice(index, 1);
                selectedIndex = -1;
                renderRows();
                return;
            }

            if (action === "edit") {
                const linea = lineas[index];
                if (!linea) {
                    return;
                }

                selectedIndex = index;
                articuloInput.value = linea.articulo ?? "";
                descripcionInput.value = linea.descripcion;
                cantidadInput.value = String(linea.cantidad);
                medidaInput.value = linea.medida ?? "";
                precioInput.value = String(linea.precio_unitario);
                margenInput.value = String(linea.margen);
                addButton.innerHTML = '<i class="far fa-save"></i> Aplicar';
                renderRows();
                autosizeDescripcion();
                descripcionInput.focus();
                return;
            }
        }

        renderRows();
    });

    tableBody.addEventListener("change", (event) => {
        const target = event.target;

        if (!pedidoMode || !(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('input[data-action="edit-cantidad"]')) {
            const index = Number(target.dataset.index);
            if (index < 0 || index >= lineas.length) {
                return;
            }

            updateLineaCantidad(index, target.value);
            renderRows();
            return;
        }

        if (target.matches('input[data-action="toggle-selected"]')) {
            const index = Number(target.dataset.index);
            if (index < 0 || index >= lineas.length) {
                return;
            }

            lineas[index].selected = target.checked;
            renderRows();
        }
    });

    if (pedidoMode && !addButton && !articuloInput && !descripcionInput) {
        selectedIndex = -1;
    }

    if (editButton && !pedidoMode) {
        editButton.addEventListener("click", () => {
            if (selectedIndex < 0 || selectedIndex >= lineas.length) {
                return;
            }

            const linea = lineas[selectedIndex];
            articuloInput.value = linea.articulo ?? "";
            descripcionInput.value = linea.descripcion;
            cantidadInput.value = String(linea.cantidad);
            medidaInput.value = linea.medida ?? "";
            precioInput.value = String(linea.precio_unitario);
            margenInput.value = String(linea.margen);
            addButton.innerHTML = '<i class="far fa-save"></i> Aplicar';
            autosizeDescripcion();
            descripcionInput.focus();
        });
    }

    if (deleteButton && !pedidoMode) {
        deleteButton.addEventListener("click", () => {
            if (selectedIndex < 0 || selectedIndex >= lineas.length) {
                return;
            }

            lineas.splice(selectedIndex, 1);
            selectedIndex = -1;
            renderRows();
        });
    }

    autosizeDescripcion();
    renderRows();
});
