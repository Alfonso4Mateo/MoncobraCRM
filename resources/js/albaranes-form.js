const moneyFormatter = new Intl.NumberFormat("es-ES", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const numberFormatter = new Intl.NumberFormat("es-ES", {
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

const round4 = (value) => {
    return Math.round(clampNumber(value) * 10000) / 10000;
};

document.addEventListener("DOMContentLoaded", () => {
    const root = document.querySelector("[data-albaran-form]");
    console.warn('[albaranes] albaranes-form.js loaded');
    if (!root) {
        return;
    }

    let pedidoMode = root.dataset.pedidoMode === "1";
    let pedidoBolsaMode = root.dataset.pedidoBolsa === "1";
    const isExistingEditForm = root.dataset.formMode === "edit";
    const isPedidoRestrictoMode = () => false;

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
                    
                    // CORRECCIÓN Camino B: Obtener y redondear la base a 2 decimales estrictos
                    const precioRaw = clampNumber(linea.precio_unitario ?? linea.precio ?? 0);
                    const margenRaw = clampNumber(linea.margen ?? 0);
                    
                    const precioUnitario = Number(precioRaw.toFixed(2));
                    const margen = Number(margenRaw.toFixed(2));
                    
                    // Calcular el precio con el margen y redondearlo a 2 decimales
                    const precioConMargen = precioUnitario * (1 + (margen / 100));
                    const precioConMargenRounded = Number(precioConMargen.toFixed(2));
                    
                    // Total definitivo con el precio base unitario ya convertido y redondeado a céntimos
                    const total = Number((cantidad * precioConMargenRounded).toFixed(2));

                    return {
                        articulo_id: linea.articulo_id ?? null,
                        articulo: String(linea.articulo ?? "").trim(),
                        descripcion: String(linea.descripcion ?? "").trim(),
                        cantidad,
                        cantidad_max: cantidadMax,
                        medida: String(linea.medida ?? linea.unidad ?? "").trim(),
                        precio_unitario: precioUnitario,
                        margen,
                        precio_con_margen: precioConMargenRounded,
                        total,
                        selected: isPedidoRestrictoMode() ? linea.selected !== false : true,
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
    // Guardamos una copia estricta de las líneas iniciales del Albarán actual (útil para el modo edición)
    const editBaseLineas = isExistingEditForm ? lineas.map((linea) => ({ ...linea })) : [];
    let activePedidoKey = null;
    let selectedIndex = -1;
    let isInitialEditLoad = isExistingEditForm;

    const autosizeDescripcion = () => {
        if (!descripcionInput || descripcionInput.tagName !== "TEXTAREA") {
            return;
        }
        descripcionInput.style.height = "auto";
        descripcionInput.style.height = `${Math.min(descripcionInput.scrollHeight, 192)}px`;
    };

    const resetInputs = () => {
        if (!descripcionInput || !cantidadInput || !precioInput || !medidaInput || !margenInput) {
            return;
        }
        descripcionInput.value = "";
        cantidadInput.value = "1";
        medidaInput.value = "und";
        precioInput.value = "0";
        margenInput.value = "0";
        autosizeDescripcion();
        descripcionInput.focus();
    };

    const syncHiddenField = () => {
        const payload = isPedidoRestrictoMode()
            ? lineas.filter((linea) => linea.selected !== false)
            : lineas;
        lineasJsonInput.value = JSON.stringify(payload);
    };

    const updateTotal = () => {
        const total = lineas.reduce((acc, linea) => acc + (isPedidoRestrictoMode() && linea.selected === false ? 0 : clampNumber(linea.total)), 0);
        totalElement.textContent = `${moneyFormatter.format(round2(total))} €`;
    };

    const lineSignature = (linea) => {
        const descripcion = String(linea?.descripcion ?? '').trim().toLowerCase();
        const medida = String(linea?.medida ?? linea?.unidad ?? '').trim().toLowerCase();
        return `${descripcion}|${medida}`;
    };

    const sumPedidoLineasBySignature = (rawLineas) => {
        const grouped = new Map();

        rawLineas.forEach((linea) => {
            if (!linea || typeof linea !== 'object') {
                return;
            }

            const descripcion = String(linea.descripcion ?? '').trim();
            if (descripcion === '') {
                return;
            }

            const signature = lineSignature(linea);
            const current = grouped.get(signature) || {
                articulo_id: linea.articulo_id ?? null,
                articulo: String(linea.articulo ?? '').trim(),
                descripcion,
                cantidad: 0,
                medida: String(linea.medida ?? linea.unidad ?? '').trim(),
                precio_unitario: round2(linea.precio_unitario ?? linea.precio ?? 0),
                margen: round2(linea.margen ?? 0),
                total: 0,
            };

            current.cantidad = round2(current.cantidad + round2(linea.cantidad ?? 0));
            
            // CORRECCIÓN Camino B: Recálculo en agrupación múltiple
            const pUnit = Number(current.precio_unitario.toFixed(2));
            const pMarg = Number(current.margen.toFixed(2));
            const pConMargen = pUnit * (1 + (pMarg / 100));
            const pConMargenRounded = Number(pConMargen.toFixed(2));
            
            current.total = Number((current.cantidad * pConMargenRounded).toFixed(2));
            
            grouped.set(signature, current);
        });

        return Array.from(grouped.values());
    };

    // CORRECCIÓN: Combinación en modo EDICIÓN
    const mergeEditLines = (pedidoLineas) => {
        const pedidoMap = new Map();
        pedidoLineas.forEach((linea) => {
            pedidoMap.set(lineSignature(linea), linea);
        });

        const merged = [];

        editBaseLineas.forEach((baseLine) => {
            const signature = lineSignature(baseLine);
            const pedidoLine = pedidoMap.get(signature);
            const orderedQuantity = round2(pedidoLine?.cantidad ?? baseLine.cantidad ?? 0);
            const currentQuantity = round2(baseLine.cantidad ?? 0);
            const maxQuantity = round2(currentQuantity + orderedQuantity);

            merged.push({
                ...baseLine,
                cantidad: currentQuantity,
                cantidad_max: Math.max(currentQuantity, maxQuantity),
                selected: true,
                locked: false,
            });
        });

        pedidoLineas.forEach((linea) => {
            const signature = lineSignature(linea);
            if (merged.some((item) => lineSignature(item) === signature)) {
                return;
            }

            merged.push({
                ...linea,
                cantidad: 0,
                cantidad_max: round2(linea.cantidad ?? 0),
                selected: false,
                locked: false,
            });
        });

        return merged;
    };

    // Calcula las cantidades restantes en modo CREACIÓN para evitar que salgan artículos ya albaranados
    const mergeCreateLinesWithRemaining = (pedidoLineas) => {
        const yaAlbaranadoMap = new Map();
        lineasFromDataset.forEach((l) => {
            const sig = lineSignature(l);
            yaAlbaranadoMap.set(sig, round2((yaAlbaranadoMap.get(sig) || 0) + l.cantidad));
        });

        return pedidoLineas.map((l) => {
            const sig = lineSignature(l);
            const consumido = yaAlbaranadoMap.get(sig) || 0;
            const cantidadRestante = Math.max(0, round2((l.cantidad ?? 0) - consumido));

            return {
                ...l,
                cantidad: cantidadRestante,
                cantidad_max: cantidadRestante,
                selected: cantidadRestante > 0, 
            };
        }).filter(l => l.cantidad_max > 0); 
    };

    const setSideButtonsState = () => {
        const hasSelection = selectedIndex >= 0 && selectedIndex < lineas.length && !lineas[selectedIndex]?.locked;

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
        
        // CORRECCIÓN Camino B: En modo de actualización de cantidad de la fila
        const pUnit = Number(clampNumber(linea.precio_unitario).toFixed(2));
        const pMarg = Number(clampNumber(linea.margen).toFixed(2));
        const pConMargen = pUnit * (1 + (pMarg / 100));
        const pConMargenRounded = Number(pConMargen.toFixed(2));
        
        linea.total = Number((cantidad * pConMargenRounded).toFixed(2));
    };

    const setPedidoMode = (enabled) => {
        pedidoMode = !!enabled;
        const restrictedMode = isPedidoRestrictoMode();

        const lineaInputRow = document.querySelector('.linea-input-row');
        if (lineaInputRow) {
            lineaInputRow.style.display = restrictedMode ? 'none' : '';
        }

        if (addButton) addButton.disabled = restrictedMode;
        if (editButton) editButton.disabled = restrictedMode || editButton.disabled;
        if (deleteButton) deleteButton.disabled = restrictedMode || deleteButton.disabled;

        const headerRow = document.querySelector('.lineas-table thead tr');
        if (headerRow) {
            if (restrictedMode) {
                headerRow.innerHTML = `
                    <th>Línea</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Medida</th>
                    <th>P. unitario</th>
                    <th>Margen</th>
                    <th>Total</th>
                    <th style="width: 7rem">Incluir</th>
                `;
            } else {
                headerRow.innerHTML = `
                    <th>Línea</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Medida</th>
                    <th>P. unitario</th>
                    <th>Margen</th>
                    <th>Total</th>
                    <th>Acción</th>
                `;
            }
        }

        if (restrictedMode) {
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
        const restrictedMode = isPedidoRestrictoMode();

        if (lineas.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="8" class="lineas-empty">${restrictedMode ? 'No hay artículos pendientes para incluir o ya han sido facturados.' : 'No hay líneas añadidas.'}</td></tr>`;
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
                const locked = linea.locked === true;
                const maxCantidad = resolveCantidadMax(linea);
                const qtyValue = Number.isFinite(linea.cantidad) ? linea.cantidad : 0;
                const rowClass = locked ? 'is-locked' : (checked ? 'is-selected' : 'is-deselected');

                if (restrictedMode) {
                    return `
                        <tr data-index="${index}" class="${rowClass}">
                            <td>${String(index + 1).padStart(2, '0')}</td>
                            <td>${linea.descripcion}</td>
                            <td>
                                <input type="number" class="albaran-line-qty" data-action="edit-cantidad" data-index="${index}" min="0" step="0.01" max="${maxCantidad}" value="${qtyValue}" ${(checked && !locked) ? '' : 'disabled'}>
                            </td>
                            <td>${medida ? medida : '<span class="text-muted">-</span>'}</td>
                            <td>${moneyFormatter.format(linea.precio_unitario)} €</td>
                            <td>${numberFormatter.format(linea.margen)} %</td>
                            <td class="linea-total">${moneyFormatter.format(totalLinea)} €</td>
                            <td>
                                <label class="albaran-line-check">
                                    <input type="checkbox" class="albaran-line-check__input" data-action="toggle-selected" data-index="${index}" ${checked ? 'checked' : ''} ${locked ? 'disabled' : ''}>
                                </label>
                            </td>
                        </tr>
                    `;
                }

                return `
                    <tr data-index="${index}"${isSelected ? ' class="is-selected"' : ""}>
                        <td>${String(index + 1).padStart(2, '0')}</td>
                        <td>${linea.descripcion}</td>
                        <td>${numberFormatter.format(linea.cantidad)}</td>
                        <td>${medida ? medida : '<span class="text-muted">-</span>'}</td>
                        <td>${moneyFormatter.format(linea.precio_unitario)} €</td>
                        <td>${numberFormatter.format(linea.margen)} %</td>
                        <td class="linea-total">${moneyFormatter.format(totalLinea)} €</td>
                        <td>
                            <button type="button" class="linea-btn linea-edit" data-action="edit" data-index="${index}" title="Editar línea">
                                <i class="far fa-edit"></i>
                            </button>
                            <button type="button" class="linea-btn linea-delete" data-action="delete" data-index="${index}" title="Eliminar línea">
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
        if (isPedidoRestrictoMode()) {
            return;
        }

        const articulo = '';
        const descripcion = descripcionInput.value.trim();
        const cantidadRaw = clampNumber(cantidadInput.value);
        const cantidad = Math.max(0, round2(cantidadRaw));
        const medida = medidaInput.value.trim() || 'und';
        
        // CORRECCIÓN Camino B: Obtener valor en bruto del usuario
        const precioUnitarioRaw = clampNumber(precioInput.value);
        const margenRaw = clampNumber(margenInput.value);

        if (!descripcion || cantidad <= 0) {
            descripcionInput.focus();
            return;
        }

        // 1. Redondear las entradas a 2 decimales PRIMERO
        const precioUnitario = Number(precioUnitarioRaw.toFixed(2));
        const margen = Number(margenRaw.toFixed(2));

        // 2. Calcular precio con margen y redondear a 2 decimales
        const precioConMargen = precioUnitario * (1 + (margen / 100));
        const precioConMargenRounded = Number(precioConMargen.toFixed(2));

        // 3. Calcular total de la línea
        const total = Number((cantidad * precioConMargenRounded).toFixed(2));

        const payload = {
            articulo_id: selectedIndex >= 0 && selectedIndex < lineas.length ? (lineas[selectedIndex].articulo_id ?? null) : null,
            articulo,
            descripcion,
            cantidad,
            medida,
            precio_unitario: precioUnitario,
            margen,
            precio_con_margen: precioConMargenRounded,
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

    if (!isPedidoRestrictoMode() && cantidadInput && precioInput && margenInput) {
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

        let selectedOption = null;
        const isInputField = pedidoClienteSelect.tagName === 'INPUT' || pedidoClienteSelect.tagName === 'TEXTAREA';

        if (isInputField) {
            const rawVal = String(pedidoClienteSelect.value || '').trim();
            if (rawVal === '') {
                clienteSelect.value = clienteSelect.value || '';
                otInput.value = otInput.value || '';
                setPedidoMode(false);
                return;
            }
            selectedOption = { value: rawVal, dataset: {} };
        } else {
            selectedOption = pedidoClienteSelect.selectedOptions && pedidoClienteSelect.selectedOptions.length > 0
                ? pedidoClienteSelect.selectedOptions[0]
                : null;
        }

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
            pedidoBolsaMode = false;
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
            pedidoBolsaMode = !!(data.bolsa ?? data.pedido_bolsa ?? false);

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
            const normalizedPedidoLineas = sumPedidoLineasBySignature(rawLineas);

            if (hasLineas) {
                if (isInitialEditLoad) {
                    // Respetamos EXACTAMENTE las líneas guardadas en el albarán
                    lineas = editBaseLineas.map(linea => ({ ...linea }));
                    isInitialEditLoad = false;
                } else if (rawLineas.length > 0) {
                    lineas = mergeCreateLinesWithRemaining(normalizedPedidoLineas);
                } else if (!isExistingEditForm) {
                    lineas = [];
                }

                renderRows();
            }

            setPedidoMode(true);
        };

        activePedidoKey = pedidoKey;
        if (!isExistingEditForm) {
            lineas = [];
        }
        pedidoBolsaMode = false;
        setPedidoMode(true);

        if (clienteId || ot) {
            applyFields({ id_cliente: clienteId, ot });
        }

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
        const $pc = window.jQuery(pedidoClienteSelect);$pc.select2({
            theme: "bootstrap4",
            width: "100%",
            placeholder: pedidoClienteSelect.dataset.placeholder || "Selecciona pedido...",
            allowClear: true,
            minimumResultsForSearch: 0,
            tags: true
        });

        $pc.on('select2:select select2:unselect select2:clear', syncPedidoClienteFields);
        $pc.on('change', syncPedidoClienteFields);$pc.trigger('change');
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

        if (isPedidoRestrictoMode() && (checkboxTarget || qtyTarget)) {
            return;
        }

        // 1. Botón de acción (editar, eliminar)
        if (target) {
            const index = Number(target.dataset.index);
            const action = target.dataset.action;

            if (isPedidoRestrictoMode() && action === "toggle-selected") {
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

        // 2. Lógica para seleccionar la fila
        if (row) {
            const index = Number(row.dataset.index);
            
            if (!isPedidoRestrictoMode() && selectedIndex !== index) {
                selectedIndex = index;

                const filaSeleccionadaPrevia = tableBody.querySelector('tr.is-selected');
                if (filaSeleccionadaPrevia) {
                    filaSeleccionadaPrevia.classList.remove('is-selected');
                }

                row.classList.add('is-selected');
                setSideButtonsState();
            } else if (isPedidoRestrictoMode()) {
                selectedIndex = index;
            }
        }
    });

    tableBody.addEventListener("change", (event) => {
        const target = event.target;

        if (!isPedidoRestrictoMode() || !(target instanceof HTMLInputElement)) {
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

    if (isPedidoRestrictoMode() && !addButton && !descripcionInput) {
        selectedIndex = -1;
    }

    if (editButton && !isPedidoRestrictoMode()) {
        editButton.addEventListener("click", () => {
            if (selectedIndex < 0 || selectedIndex >= lineas.length) {
                return;
            }

            const linea = lineas[selectedIndex];
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

    if (deleteButton && !isPedidoRestrictoMode()) {
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