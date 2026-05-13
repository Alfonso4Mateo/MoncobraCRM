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
    if (!root) {
        return;
    }

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

    if (!articuloInput || !descripcionInput || !cantidadInput || !precioInput || !medidaInput || !margenInput || !addButton || !tableBody || !totalElement || !lineasJsonInput) {
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
                    const precioUnitario = round2(linea.precio_unitario ?? linea.precio);
                    const margen = round2(linea.margen);
                    const total = round2(cantidad * precioUnitario * (1 + margen / 100));

                    return {
                        articulo_id: linea.articulo_id ?? null,
                        articulo: String(linea.articulo ?? "").trim(),
                        descripcion: String(linea.descripcion ?? "").trim(),
                        cantidad,
                        medida: String(linea.medida ?? linea.unidad ?? "").trim(),
                        precio_unitario: precioUnitario,
                        margen,
                        total,
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
    let selectedIndex = -1;

    const autosizeDescripcion = () => {
        if (descripcionInput.tagName !== "TEXTAREA") {
            return;
        }

        descripcionInput.style.height = "auto";
        descripcionInput.style.height = `${Math.min(descripcionInput.scrollHeight, 192)}px`;
    };

    const resetInputs = () => {
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
        lineasJsonInput.value = JSON.stringify(lineas);
    };

    const updateTotal = () => {
        const total = lineas.reduce((acc, linea) => acc + clampNumber(linea.total), 0);
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

    const renderRows = () => {
        if (lineas.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="9" class="lineas-empty">No hay lineas añadidas.</td></tr>';
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

    addButton.addEventListener("click", saveCurrentInputs);

    [cantidadInput, precioInput, margenInput].forEach((input) => {
        input.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                saveCurrentInputs();
            }
        });
    });

    descripcionInput.addEventListener("input", autosizeDescripcion);

    tableBody.addEventListener("click", (event) => {
        const target = event.target.closest("button[data-action]");
        const row = event.target.closest("tr[data-index]");

        if (row) {
            selectedIndex = Number(row.dataset.index);
        }

        if (target) {
            const index = Number(target.dataset.index);
            const action = target.dataset.action;

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

    if (editButton) {
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

    if (deleteButton) {
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
