const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));

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

const round4 = (value) => Math.round(clampNumber(value) * 100) / 100;

document.addEventListener("DOMContentLoaded", () => {
    const root = document.querySelector("[data-albaran-form]");
    if (!root) {
        return;
    }

    let pedidoMode = root.dataset.pedidoMode === "1";
    let pedidoBolsaMode = root.dataset.pedidoBolsa === "1";
    const isExistingEditForm = root.dataset.formMode === "edit";
    
    const descripcionInput = document.getElementById("linea_descripcion");
    const cantidadInput = document.getElementById("linea_cantidad");
    const precioInput = document.getElementById("linea_precio");
    const medidaInput = document.getElementById("linea_medida");
    const margenInput = document.getElementById("linea_margen");
    const addButton = document.getElementById("btnAddLinea");
    const editButton = document.getElementById("btnEditLinea");
    const deleteButton = document.getElementById("btnDeleteLinea");
    
    const tableContainer = document.querySelector('.table-responsive') || document.getElementById("lineasBody")?.parentElement;
    const totalElement = document.getElementById("albaranTotalValue");
    const lineasJsonInput = document.getElementById("lineasJson");
    const pedidoClienteSelect = document.getElementById("pedido_cliente");
    const clienteSelect = document.getElementById("cliente_id");
    const otInput = document.getElementById("ot");

    if (!tableContainer || !totalElement || !lineasJsonInput) {
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
                    const cantidad = round4(linea.cantidad);
                    const cantidadMax = Math.max(cantidad, round4(linea.cantidad_max ?? linea.cantidad));
                    const precioUnitario = round4(linea.precio_unitario ?? linea.precio);
                    const margen = round2(linea.margen);
                    const total = linea.modo_consumo === "importe"
                        && linea.total !== undefined
                        && linea.total !== null
                        ? round2(linea.total)
                        : round2(cantidad * precioUnitario * (1 + margen / 100));

                    return {
                        uid: linea.uid ?? null,
                        linea_id: linea.linea_id ?? null,
                        pedido_id: linea.pedido_id ?? null,
                        numero_pedido: linea.numero_pedido ?? null,
                        modo_consumo: linea.modo_consumo === "importe" ? "importe" : "cantidad",
                        articulo_id: linea.articulo_id ?? null,
                        articulo: String(linea.articulo ?? "").trim(),
                        descripcion: String(linea.descripcion ?? "").trim(),
                        cantidad,
                        cantidad_max: cantidadMax,
                        medida: String(linea.medida ?? linea.unidad ?? "").trim(),
                        precio_unitario: precioUnitario,
                        margen,
                        total,
                        importe_disponible: linea.importe_disponible ?? null,
                        num_partidas: linea.num_partidas ?? 1,
                        repartos: Array.isArray(linea.repartos) ? linea.repartos : null,
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
    const hasOldData = lineasFromInput.length > 0;
    const editBaseLineas = (isExistingEditForm || hasOldData) ? lineas.map((linea) => ({ ...linea })) : [];
    let activePedidoKey = null;
    let selectedIndex = -1;
    let isInitialEditLoad = isExistingEditForm || hasOldData;
    let repartoPanelIndex = -1;
    let repartoPanelData = null;

    const repartoDesgloseCache = new Map();

    const fetchLineaDesglose = (lineaId) => {
        if (repartoDesgloseCache.has(lineaId)) {
            return Promise.resolve(repartoDesgloseCache.get(lineaId));
        }
        return fetch(`/lineas-bolsa/${encodeURIComponent(lineaId)}/desglose`, {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        })
            .then((resp) => (resp.ok ? resp.json() : { partidas: [] }))
            .then((data) => {
                repartoDesgloseCache.set(lineaId, data);
                return data;
            })
            .catch(() => ({ partidas: [] }));
    };

    const createElement = (tagName, className, text) => {
        const element = document.createElement(tagName);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };

    const renderRepartoPanel = (index) => {
        const linea = lineas[index];
        if (!repartoPanelData) {
            const row = createElement("tr", "reparto-panel-row");
            const cell = createElement("td", null, "Cargando partidas…");
            cell.colSpan = 8;
            row.appendChild(cell);
            return row;
        }

        const partidas = repartoPanelData.partidas || [];
        const repartoActual = Array.isArray(linea.repartos) ? linea.repartos : [];

        const row = createElement("tr", "reparto-panel-row");
        const cell = createElement("td");
        cell.colSpan = 8;
        cell.style.cssText = "background:#f8fbff; padding:12px; border-left: 4px solid #1f74dd;";

        const title = createElement("strong", null, `Reparto manual — ${linea.descripcion}`);
        cell.appendChild(title);

        const partidasContainer = createElement("div");
        partidasContainer.style.marginTop = "8px";
        if (partidas.length === 0) {
            partidasContainer.appendChild(createElement("span", "text-muted", "Esta línea no tiene partidas con saldo."));
        } else {
            partidas.forEach((p) => {
                const valorActual = repartoActual.find((r) => r.pedido_id === p.pedido_id)?.importe ?? "";
                const partida = createElement("div");
                partida.style.cssText = "display:flex; align-items:center; gap:8px; margin-bottom:6px;";
                const partidaLabel = createElement("span");
                partidaLabel.style.flex = "1";
                partidaLabel.appendChild(document.createTextNode(`Partida ${p.numero_pedido ?? `#${p.pedido_id}`} `));
                const disponible = createElement("small", "text-muted", `(disponible: ${moneyFormatter.format(p.importe_disponible)} €)`);
                partidaLabel.appendChild(disponible);
                const input = createElement("input");
                input.type = "number";
                input.min = "0";
                input.step = "0.01";
                input.max = String(p.importe_disponible);
                input.dataset.repartoPedido = String(p.pedido_id);
                input.value = String(valorActual);
                input.style.width = "130px";
                partida.append(partidaLabel, input);
                partidasContainer.appendChild(partida);
            });
        }
        cell.appendChild(partidasContainer);

        const buttons = createElement("div");
        buttons.style.cssText = "margin-top:10px; display:flex; gap:8px;";
        [
            ["reparto-guardar", "Guardar reparto"],
            ["reparto-auto", "Usar reparto automático"],
            ["reparto-cerrar", "Cerrar"],
        ].forEach(([action, label]) => {
            const button = createElement("button", "linea-btn", label);
            button.type = "button";
            button.dataset.action = action;
            button.dataset.index = String(index);
            buttons.appendChild(button);
        });
        cell.appendChild(buttons);
        row.appendChild(cell);
        return row;
    };

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
        const payload = pedidoMode
            ? lineas.filter((linea) => linea.selected !== false)
            : lineas;
        lineasJsonInput.value = JSON.stringify(payload);
    };

    const updateTotal = () => {
        const total = lineas.reduce((acc, linea) => acc + (pedidoMode && linea.selected === false ? 0 : clampNumber(linea.total)), 0);
        totalElement.textContent = `${moneyFormatter.format(round4(total))} €`;
    };

    const getMaxTotal = (linea) => {
        if (linea.importe_disponible !== null && linea.importe_disponible !== undefined) {
            return round4(linea.importe_disponible);
        }
        const unitPriceWithMargin = linea.precio_unitario * (1 + linea.margen / 100);
        return round4(resolveCantidadMax(linea) * unitPriceWithMargin);
    };

    const setSaldoStatus = (kind, text) => {
        let el = document.getElementById("albaran-saldo-status");
        if (!text) {
            if (el) el.remove();
            return;
        }
        if (!el) {
            el = document.createElement("div");
            el.id = "albaran-saldo-status";
            el.style.cssText = "margin:8px 0;padding:8px 12px;border-radius:8px;font-size:0.85em;";
            tableContainer.parentElement.insertBefore(el, tableContainer);
        }
        el.style.background = kind === "ok" ? "#eafaf1" : "#fdecea";
        el.style.color = kind === "ok" ? "#1e7e4a" : "#a93226";
        el.textContent = text;
    };

    const dedupeLineasPorUid = (lineasV3) => {
        const porUid = new Map();

        lineasV3.forEach((linea) => {
            const existente = porUid.get(linea.uid);
            if (!existente) {
                porUid.set(linea.uid, { ...linea, numeros_pedido: [linea.numero_pedido].filter(Boolean) });
                return;
            }
            if (linea.numero_pedido && !existente.numeros_pedido.includes(linea.numero_pedido)) {
                existente.numeros_pedido.push(linea.numero_pedido);
            }
        });

        return Array.from(porUid.values()).map((linea) => ({
            ...linea,
            numero_pedido: linea.numeros_pedido.join(', '),
        }));
    };

    const formatLineasBolsaV3 = (lineasV3, numeroPedido, pedidoId = null) => {
        return lineasV3
            .map((l) => {
                const modoImporte = l.modo_consumo === "importe";
                const precioUnitario = round4(l.precio_unitario ?? 0);
                const margen = round2(l.margen ?? 0);
                const unitPriceWithMargin = precioUnitario * (1 + margen / 100);
                const disponible = round4(l.importe_disponible ?? 0);

                const cantidadRestante = modoImporte
                    ? 1
                    : (unitPriceWithMargin > 0 ? round4(disponible / unitPriceWithMargin) : 0);

                return {
                    uid: l.uid,
                    linea_id: l.linea_id,
                    pedido_id: pedidoId ? Number(pedidoId) : null,
                    numero_pedido: numeroPedido,
                    modo_consumo: modoImporte ? "importe" : "cantidad",
                    articulo_id: null,
                    articulo: String(l.articulo ?? "").trim(),
                    descripcion: l.descripcion,
                    cantidad: cantidadRestante,
                    cantidad_max: cantidadRestante,
                    medida: String(l.medida ?? "").trim(),
                    precio_unitario: precioUnitario,
                    margen,
                    total: 0,
                    importe_disponible: disponible,
                    num_partidas: l.num_partidas ?? 1,
                    repartos: null,
                    selected: false,
                };
            })
            .filter((l) => l.importe_disponible > 0);
    };

    const setSideButtonsState = () => {
        const hasSelection = selectedIndex >= 0 && selectedIndex < lineas.length && !lineas[selectedIndex]?.locked;
        if (editButton) editButton.disabled = !hasSelection;
        if (deleteButton) deleteButton.disabled = !hasSelection;
    };

    const resolveCantidadMax = (linea) => {
        const current = round4(linea?.cantidad ?? 0);
        const maxValue = round4(linea?.cantidad_max ?? linea?.cantidad ?? 0);
        return Math.max(current, maxValue);
    };

    const setPedidoMode = (enabled) => {
        pedidoMode = !!enabled;
        const restrictedMode = pedidoMode;

        const lineaInputRow = document.querySelector('.linea-input-row');
        if (lineaInputRow) {
            lineaInputRow.style.display = restrictedMode ? 'none' : '';
        }

        if (addButton) addButton.disabled = restrictedMode;
        if (editButton) editButton.disabled = restrictedMode || editButton.disabled;
        if (deleteButton) deleteButton.disabled = restrictedMode || deleteButton.disabled;

        if (restrictedMode) {
            let note = document.querySelector('.albaran-selection-note');
            if (!note) {
                note = document.createElement('div');
                note.className = 'albaran-selection-note';
                note.innerHTML = '<i class="fas fa-boxes" aria-hidden="true"></i> <strong>Modo Bolsa:</strong> Puedes ajustar el consumo introduciendo las <strong>unidades</strong> o el <strong>importe total a facturar</strong>.';
                const articulosCard = document.querySelector('.albaran-card h2');
                if (articulosCard && articulosCard.textContent && articulosCard.textContent.trim().toUpperCase().includes('ARTICULOS')) {
                    articulosCard.parentElement.insertBefore(note, articulosCard.parentElement.querySelector('.linea-input-row') || tableContainer);
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
        const restrictedMode = pedidoMode;

        if (lineas.length === 0) {
            tableContainer.innerHTML = `
                <table class="table lineas-table">
                    <thead>
                        <tr>
                            ${restrictedMode
                                ? `<th>Línea</th><th>Descripción</th><th style="width: 120px;">Cantidad</th><th>P. unitario</th><th style="width: 170px;">Importe a facturar</th><th style="width: 5rem">Incluir</th>`
                                : `<th>Línea</th><th>Descripción</th><th>Cantidad</th><th>Medida</th><th>P. unitario</th><th>Margen</th><th>Total</th><th>Acción</th>`
                            }
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="8" class="lineas-empty text-center">${restrictedMode ? 'No hay artículos pendientes para incluir o ya han sido facturados.' : 'No hay líneas añadidas.'}</td></tr>
                    </tbody>
                </table>`;
            selectedIndex = -1;
            setSideButtonsState();
            syncHiddenField();
            updateTotal();
            return;
        }

        const grupos = {};
        lineas.forEach((linea, index) => {
            const key = linea.numero_pedido || 'default';
            if (!grupos[key]) grupos[key] = [];
            grupos[key].push({ linea, originalIndex: index }); 
        });

        tableContainer.replaceChildren();

        for (const [key, items] of Object.entries(grupos)) {
            const groupFragment = document.createDocumentFragment();
            if (key !== 'default') {
                const groupHeader = createElement("div");
                groupHeader.style.cssText = "background:#eaf2fb; padding:10px 16px; border-radius:8px 8px 0 0; margin-top: 1.5rem; border: 1px solid #c7d9ee; border-bottom: none; display: flex; align-items: center; gap: 10px;";
                const icon = createElement("i", "fas fa-box-open");
                icon.style.cssText = "color: #2a6fb0; font-size: 1.1em;";
                const groupTitle = createElement("strong", null, `Bolsa: ${key}`);
                groupTitle.style.cssText = "color: #17385d; font-size: 1rem;";
                const count = createElement("span", null, `${items.length} ${items.length === 1 ? "línea" : "líneas"}`);
                count.style.cssText = "background: #ffffff; border: 1px solid #c7d9ee; color: #64748b; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: bold;";
                groupHeader.append(icon, groupTitle, count);
                groupFragment.appendChild(groupHeader);
            }

            const table = createElement("table", "table lineas-table");
            table.style.cssText = key !== "default"
                ? "border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px -4px rgba(0,0,0,0.05); margin-bottom: 20px;"
                : "margin-bottom: 20px;";
            const thead = createElement("thead");
            if (key !== "default") thead.style.background = "#f8fafc";
            const headerRow = createElement("tr");
            const headers = restrictedMode
                ? ["Línea", "Descripción", "Cantidad", "P. unitario", "Importe a facturar", "Incluir"]
                : ["Línea", "Descripción", "Cantidad", "Medida", "P. unitario", "Margen", "Total", "Acción"];
            headers.forEach((header, headerIndex) => {
                const cell = createElement("th", null, header);
                if (restrictedMode && (headerIndex === 2 || headerIndex === 4)) {
                    cell.style.width = headerIndex === 2 ? "120px" : "170px";
                }
                if (restrictedMode && headerIndex === 5) cell.style.width = "5rem";
                headerRow.appendChild(cell);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);
            const tbody = createElement("tbody");

            items.forEach((item, visualIndex) => {
                const { linea, originalIndex } = item;
                const isSelected = originalIndex === selectedIndex;
                const medida = String(linea.medida ?? "").trim();
                const totalLinea = clampNumber(linea.total);
                const checked = linea.selected !== false;
                const locked = linea.locked === true;
                
                const maxTotal = getMaxTotal(linea);
                const rowClass = locked ? 'is-locked' : (checked ? 'is-selected' : 'is-deselected');
                const precioFinal = clampNumber(linea.precio_unitario * (1 + (linea.margen / 100)));

                if (restrictedMode) {
                    const puedeRepartir = linea.modo_consumo === "importe" && (linea.num_partidas ?? 1) > 1;
                    const repartoActivo = Array.isArray(linea.repartos) && linea.repartos.length > 0;
                    const row = createElement("tr", rowClass);
                    row.dataset.index = String(originalIndex);
                    const descriptionCell = createElement("td", null, linea.descripcion);
                    descriptionCell.style.whiteSpace = "pre-wrap";
                    row.append(
                        createElement("td", null, String(visualIndex + 1).padStart(2, "0")),
                        descriptionCell
                    );
                    const quantityCell = createElement("td");
                    if (linea.modo_consumo === "importe") {
                        const quantityText = createElement("span", "text-muted", `${numberFormatter.format(linea.cantidad)}${medida}`);
                        quantityText.style.fontSize = "0.9em";
                        quantityCell.appendChild(quantityText);
                    } else {
                        const quantityWrapper = createElement("div");
                        quantityWrapper.style.cssText = "display: flex; align-items: center; gap: 5px;";
                        const quantityInput = createElement("input");
                        quantityInput.type = "number";
                        quantityInput.className = "albaran-line-qty";
                        quantityInput.dataset.action = "edit-cantidad";
                        quantityInput.dataset.index = String(originalIndex);
                        quantityInput.min = "0";
                        quantityInput.step = "0.01";
                        quantityInput.max = String(linea.cantidad_max);
                        quantityInput.value = String(linea.cantidad);
                        quantityInput.disabled = !(checked && !locked);
                        quantityInput.style.width = "75px";
                        const measureText = createElement("span", "text-muted", medida);
                        measureText.style.fontSize = "0.9em";
                        quantityWrapper.append(quantityInput, measureText);
                        const maxText = createElement("div", "text-muted", `Max: ${linea.cantidad_max}`);
                        maxText.style.cssText = "font-size:0.78em;margin-top:3px;";
                        quantityCell.append(quantityWrapper, maxText);
                    }
                    row.appendChild(quantityCell);
                    row.appendChild(createElement("td", null, `${moneyFormatter.format(precioFinal)} €`));
                    const totalCell = createElement("td");
                    const totalWrapper = createElement("div");
                    totalWrapper.style.cssText = "display: flex; align-items: center; gap: 5px;";
                    const totalInput = createElement("input");
                    totalInput.type = "number";
                    totalInput.className = "albaran-line-qty";
                    totalInput.dataset.action = "edit-total";
                    totalInput.dataset.index = String(originalIndex);
                    totalInput.min = "0";
                    totalInput.step = "0.01";
                    totalInput.max = String(maxTotal);
                    totalInput.value = String(totalLinea);
                    totalInput.disabled = !(checked && !locked);
                    totalWrapper.append(totalInput, createElement("span", "text-muted", "€"));
                    const availableText = createElement("div", "text-muted", `Disponible: ${moneyFormatter.format(maxTotal)} €`);
                    availableText.style.cssText = "font-size:0.78em;margin-top:3px;";
                    const warning = createElement("div", "linea-aviso", linea.aviso ? linea.aviso : "");
                    warning.dataset.avisoFor = String(originalIndex);
                    warning.style.cssText = "font-size:0.8em;margin-top:3px;color:#c0392b;font-weight:600;";
                    totalCell.append(totalWrapper, availableText, warning);
                    if (puedeRepartir) {
                        const repartoButton = createElement("button", "linea-btn linea-reparto", repartoActivo ? "Reparto manual ✓" : "Repartir entre partidas");
                        repartoButton.type = "button";
                        repartoButton.dataset.action = "reparto";
                        repartoButton.dataset.index = String(originalIndex);
                        repartoButton.style.cssText = "margin-top:4px; font-size:0.8em;";
                        repartoButton.title = "Elegir de qué partida sale este importe";
                        totalCell.appendChild(repartoButton);
                    }
                    row.appendChild(totalCell);
                    const includeCell = createElement("td");
                    const label = createElement("label", "albaran-line-check");
                    const checkbox = createElement("input", "albaran-line-check__input");
                    checkbox.type = "checkbox";
                    checkbox.dataset.action = "toggle-selected";
                    checkbox.dataset.index = String(originalIndex);
                    checkbox.checked = checked;
                    checkbox.disabled = locked;
                    label.appendChild(checkbox);
                    includeCell.appendChild(label);
                    row.appendChild(includeCell);
                    tbody.appendChild(row);
                } else {
                    const row = createElement("tr", isSelected ? "is-selected" : "");
                    row.dataset.index = String(originalIndex);
                    const descriptionCell = createElement("td", null, linea.descripcion);
                    descriptionCell.style.whiteSpace = "pre-wrap";
                    row.append(
                        createElement("td", null, String(visualIndex + 1).padStart(2, "0")),
                        descriptionCell,
                        createElement("td", null, numberFormatter.format(linea.cantidad))
                    );
                    const measureCell = createElement("td");
                    measureCell.appendChild(medida ? createElement("span", null, medida) : createElement("span", "text-muted", "-"));
                    row.append(measureCell, createElement("td", null, `${moneyFormatter.format(precioFinal)} €`), createElement("td", null, `${numberFormatter.format(linea.margen)} %`));
                    row.appendChild(createElement("td", "linea-total", `${moneyFormatter.format(totalLinea)} €`));
                    const actions = createElement("td");
                    [["linea-edit", "far fa-edit", "edit", "Editar línea"], ["linea-delete", "far fa-trash-alt", "delete", "Eliminar línea"]].forEach(([className, iconClass, action, title]) => {
                        const button = createElement("button", `linea-btn ${className}`);
                        button.type = "button";
                        button.dataset.action = action;
                        button.dataset.index = String(originalIndex);
                        button.title = title;
                        button.appendChild(createElement("i", iconClass));
                        actions.appendChild(button);
                    });
                    row.appendChild(actions);
                    tbody.appendChild(row);
                }
                if (repartoPanelIndex === originalIndex) tbody.appendChild(renderRepartoPanel(originalIndex));
            });

            table.appendChild(tbody);
            groupFragment.appendChild(table);
            tableContainer.appendChild(groupFragment);
        }

        setSideButtonsState();
        syncHiddenField();
        updateTotal();
    };

    const saveCurrentInputs = () => {
        if (pedidoMode) {
            return;
        }

        const articulo = '';
        const descripcion = descripcionInput.value.trim();
        const cantidad = round4(cantidadInput.value);
        const medida = medidaInput.value.trim() || 'und';
        const precioUnitario = round4(precioInput.value);
        const margen = round2(margenInput.value);

        if (!descripcion || cantidad <= 0) {
            descripcionInput.focus();
            return;
        }

        const total = round4(cantidad * precioUnitario * (1 + margen / 100));

        const payload = {
            uid: selectedIndex >= 0 && selectedIndex < lineas.length ? (lineas[selectedIndex].uid ?? null) : null,
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

        const selectedOptions = Array.from(pedidoClienteSelect.selectedOptions);

        if (selectedOptions.length === 0) {
            if (clienteSelect.tomselect) {
                clienteSelect.tomselect.clear();
            } else {
                clienteSelect.value = "";
            }
            otInput.value = "";
            pedidoBolsaMode = false;
            setPedidoMode(false);
            activePedidoKey = null;
            if (!isInitialEditLoad) {
                lineas = [];
                renderRows();
            }
            return;
        }

        const pedidos = selectedOptions.map(opt => ({
            id: opt.dataset.pedidoId,
            numero: opt.value,
            clienteId: opt.dataset.clienteId,
            ot: opt.dataset.ot
        }));

        const currentKey = pedidos.map(p => p.id).sort().join(',');
        
        if (activePedidoKey === currentKey) {
            return; 
        }
        activePedidoKey = currentKey;

        const firstPedido = pedidos[0];
        if (firstPedido.clienteId) {
            if (clienteSelect.tomselect) {
                clienteSelect.tomselect.setValue(String(firstPedido.clienteId));
            } else {
                clienteSelect.value = String(firstPedido.clienteId);
                clienteSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        if (firstPedido.ot) {
            otInput.value = firstPedido.ot;
        }

        if (isInitialEditLoad) {
            isInitialEditLoad = false;
            pedidoBolsaMode = true;
            setPedidoMode(true);
            const fetchPromises = pedidos.map(pedido => {
                if (!pedido.id) return Promise.resolve([]);

                return fetch(`/pedidos-clientes/${encodeURIComponent(pedido.id)}/lineas-bolsa`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then(resp => resp.ok ? resp.json() : null)
                    .then(data => data && Array.isArray(data.lineas)
                        ? formatLineasBolsaV3(data.lineas, data.numero_pedido || pedido.numero, pedido.id)
                        : [])
                    .catch(() => []);
            });

            Promise.all(fetchPromises).then(resultados => {
                const disponibles = dedupeLineasPorUid(resultados.flat());
                const actuales = editBaseLineas.map(linea => ({ ...linea }));

                lineas = disponibles.length === 0
                    ? actuales
                    : disponibles.map(disponible => {
                        const actual = actuales.find(linea =>
                            (disponible.uid && linea.uid === disponible.uid)
                            || (disponible.linea_id && Number(linea.linea_id) === Number(disponible.linea_id))
                        );

                        if (!actual) return disponible;

                        const importeActual = round2(actual.total ?? 0);
                        const importeDisponible = round2(
                            Number(disponible.importe_disponible ?? 0) + importeActual
                        );
                        const unitPriceWithMargin = round2(
                            Number(disponible.precio_unitario ?? 0)
                            * (1 + Number(disponible.margen ?? 0) / 100)
                        );

                        return {
                            ...actual,
                            uid: disponible.uid ?? actual.uid,
                            linea_id: disponible.linea_id ?? actual.linea_id,
                            pedido_id: disponible.pedido_id ?? actual.pedido_id,
                            importe_disponible: importeDisponible,
                            cantidad_max: disponible.modo_consumo === "importe"
                                ? 1
                                : (unitPriceWithMargin > 0
                                    ? round2(importeDisponible / unitPriceWithMargin)
                                    : round2(actual.cantidad ?? 0)),
                        };
                    });

                renderRows();
            });
            return;
        }

        pedidoBolsaMode = true;
        setPedidoMode(true);
        setSaldoStatus('ok', 'Cargando saldo de las bolsas...');

        const fetchPromises = pedidos.map(pedido => {
            if (!pedido.id) return Promise.resolve([]);

            return fetch(`/pedidos-clientes/${encodeURIComponent(pedido.id)}/lineas-bolsa`, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
            .then(resp => resp.ok ? resp.json() : null)
            .then(data => {
                if (data && Array.isArray(data.lineas)) {
                    return formatLineasBolsaV3(data.lineas, data.numero_pedido || pedido.numero, pedido.id);
                }
                return [];
            })
            .catch(err => {
                return [];
            });
        });

        Promise.all(fetchPromises).then(resultados => {
            lineas = dedupeLineasPorUid(resultados.flat());
            
            if (lineas.length > 0) {
                setSaldoStatus('ok', 'Saldo real cargado: Se muestran las líneas disponibles de todas las bolsas seleccionadas.');
            } else {
                setSaldoStatus('error', 'Las bolsas seleccionadas no tienen líneas con saldo disponible.');
            }
            
            renderRows();
        });
    };

    if (clienteSelect) {
        new TomSelect(clienteSelect, { create: false });
    }

    if (pedidoClienteSelect) {
        new TomSelect(pedidoClienteSelect, {
            plugins: ["remove_button"],
            create: false,
            placeholder: pedidoClienteSelect.dataset.placeholder || "Selecciona pedido..."
        });

        pedidoClienteSelect.addEventListener("change", syncPedidoClienteFields);
        syncPedidoClienteFields();
    }

    tableContainer.addEventListener("click", (event) => {
        const checkboxTarget = event.target.closest('input[data-action="toggle-selected"]');
        const qtyTarget = event.target.closest('input[data-action="edit-total"]');
        const udTarget = event.target.closest('input[data-action="edit-cantidad"]');
        const target = event.target.closest("button[data-action]");
        const row = event.target.closest("tr[data-index]");

        if (pedidoMode && (checkboxTarget || qtyTarget || udTarget)) {
            return;
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
                if (!linea) return;

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

            if (action === "reparto") {
                const linea = lineas[index];
                if (!linea || !linea.linea_id) return;

                repartoPanelIndex = index;
                repartoPanelData = null;
                renderRows();

                fetchLineaDesglose(linea.linea_id).then((data) => {
                    if (repartoPanelIndex !== index) return; 
                    repartoPanelData = data;
                    renderRows();
                });
                return;
            }

            if (action === "reparto-cerrar") {
                repartoPanelIndex = -1;
                repartoPanelData = null;
                renderRows();
                return;
            }

            if (action === "reparto-auto") {
                const linea = lineas[index];
                if (linea) linea.repartos = null;
                repartoPanelIndex = -1;
                repartoPanelData = null;
                renderRows();
                return;
            }

            if (action === "reparto-guardar") {
                const linea = lineas[index];
                if (!linea) return;

                const panelRow = tableContainer.querySelector(".reparto-panel-row");
                const inputs = panelRow ? panelRow.querySelectorAll("input[data-reparto-pedido]") : [];

                const reparto = Array.from(inputs)
                    .map((input) => ({
                        pedido_id: Number(input.dataset.repartoPedido),
                        importe: round4(input.value),
                    }))
                    .filter((item) => item.pedido_id > 0 && item.importe > 0);

                const sumaReparto = reparto.reduce((acc, item) => acc + item.importe, 0);
                if (reparto.length > 0 && round4(sumaReparto) !== round4(linea.total)) {
                    // eslint-disable-next-line no-alert
                    alert(`El reparto (${moneyFormatter.format(sumaReparto)} €) no coincide con el importe de la línea (${moneyFormatter.format(linea.total)} €). Ajusta los importes antes de guardar.`);
                    return;
                }

                linea.repartos = reparto.length > 0 ? reparto : null;
                repartoPanelIndex = -1;
                repartoPanelData = null;
                renderRows();
                return;
            }
        }

        if (row) {
            const index = Number(row.dataset.index);
            if (!pedidoMode && selectedIndex !== index) {
                selectedIndex = index;
                const filaSeleccionadaPrevia = tableContainer.querySelector('tr.is-selected');
                if (filaSeleccionadaPrevia) filaSeleccionadaPrevia.classList.remove('is-selected');
                row.classList.add('is-selected');
                setSideButtonsState();
            } else if (pedidoMode) {
                selectedIndex = index;
            }
        }
    });

    tableContainer.addEventListener("input", (event) => {
        const target = event.target;
        if (!pedidoMode || !(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('input[data-action="edit-total"]')) {
            const index = Number(target.dataset.index);
            const linea = lineas[index];
            if (!linea) return;
            
            const maxTotal = getMaxTotal(linea);
            const aviso = tableContainer.querySelector(`[data-aviso-for="${index}"]`);
            const excede = Number.isFinite(maxTotal) && round4(target.value) > maxTotal + 0.001;
            
            target.style.borderColor = excede ? "#c0392b" : "";
            if (aviso) {
                aviso.textContent = excede
                    ? `Límite superado. Máximo disponible: ${moneyFormatter.format(maxTotal)} €.`
                    : "";
            }
        } else if (target.matches('input[data-action="edit-cantidad"]')) {
            const index = Number(target.dataset.index);
            const linea = lineas[index];
            if (!linea) return;
            
            const maxCantidad = linea.cantidad_max;
            const aviso = tableContainer.querySelector(`[data-aviso-for="${index}"]`);
            const excede = Number.isFinite(maxCantidad) && round4(target.value) > maxCantidad + 0.001;
            
            target.style.borderColor = excede ? "#c0392b" : "";
            if (aviso) {
                aviso.textContent = excede
                    ? `Límite superado. Máximo: ${maxCantidad} ${linea.medida}.`
                    : "";
            }
        }
    });

    tableContainer.addEventListener("change", (event) => {
        const target = event.target;
        if (!pedidoMode || !(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('input[data-action="edit-cantidad"]')) {
            const index = Number(target.dataset.index);
            if (index < 0 || index >= lineas.length) return;

            const linea = lineas[index];
            if (linea.modo_consumo === "importe") return;

            const unitPriceWithMargin = linea.precio_unitario * (1 + linea.margen / 100);
            const maxCantidad = linea.cantidad_max;

            let newQty = round4(target.value);
            linea.aviso = null;
            
            // --- CAMBIO: Solo mostramos el aviso, pero NO sobreescribimos newQty = maxCantidad ---
            if (Number.isFinite(maxCantidad) && newQty > maxCantidad + 0.001) {
                linea.aviso = `Has escrito ${newQty} ${linea.medida}, pero solo quedan ${maxCantidad} disponibles. Ajusta el valor antes de guardar.`;
            }

            linea.cantidad = newQty;
            linea.total = round4(newQty * unitPriceWithMargin);
            linea.repartos = null;

            if (newQty > 0 && !linea.locked) {
                linea.selected = true;
            } else if (newQty === 0 && !linea.locked) {
                linea.selected = false;
            }

            renderRows();
            return;
        }

        if (target.matches('input[data-action="edit-total"]')) {
            const index = Number(target.dataset.index);
            if (index < 0 || index >= lineas.length) return;

            const linea = lineas[index];
            const esImporte = linea.modo_consumo === "importe";
            const unitPriceWithMargin = linea.precio_unitario * (1 + linea.margen / 100);
            const maxTotal = getMaxTotal(linea);

            let newTotal = round4(target.value); 
            linea.aviso = null;
            
            // --- CAMBIO: Solo mostramos el aviso, pero NO sobreescribimos newTotal = maxTotal ---
            if (Number.isFinite(maxTotal) && newTotal > maxTotal + 0.001) {
                linea.aviso = `Has escrito ${moneyFormatter.format(newTotal)} €, pero solo quedan ${moneyFormatter.format(maxTotal)} € disponibles. Ajusta el valor antes de guardar.`;
            }

            linea.total = newTotal;

            if (esImporte) {
                linea.cantidad = 1;
            } else if (unitPriceWithMargin > 0) {
                linea.cantidad = round4(newTotal / unitPriceWithMargin);
            } else {
                linea.cantidad = 0;
            }

            linea.repartos = null;

            if (newTotal > 0 && !linea.locked) {
                linea.selected = true;
            } else if (newTotal === 0 && !linea.locked) {
                linea.selected = false;
            }

            renderRows();
            return;
        }

        if (target.matches('input[data-action="toggle-selected"]')) {
            const index = Number(target.dataset.index);
            if (index < 0 || index >= lineas.length) return;

            lineas[index].selected = target.checked;
            
            if (target.checked && lineas[index].total === 0) {
                 const unitPriceWithMargin = lineas[index].precio_unitario * (1 + lineas[index].margen / 100);
                 lineas[index].total = getMaxTotal(lineas[index]);
                 lineas[index].cantidad = lineas[index].modo_consumo === "importe"
                     ? 1
                     : (unitPriceWithMargin > 0 ? round4(lineas[index].total / unitPriceWithMargin) : 0);
            }
            lineas[index].aviso = null;

            renderRows();
        }
    });

    if (pedidoMode && !addButton && !descripcionInput) {
        selectedIndex = -1;
    }

    if (editButton && !pedidoMode) {
        editButton.addEventListener("click", () => {
            if (selectedIndex < 0 || selectedIndex >= lineas.length) return;

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

    if (deleteButton && !pedidoMode) {
        deleteButton.addEventListener("click", () => {
            if (selectedIndex < 0 || selectedIndex >= lineas.length) return;

            lineas.splice(selectedIndex, 1);
            selectedIndex = -1;
            renderRows();
        });
    }

    autosizeDescripcion();
    renderRows();
});