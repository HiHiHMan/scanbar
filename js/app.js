(() => {
  const Icons = {
    iconMarkup(name) {
      return '<svg class="ui-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + (iconPaths[name] || '') + '</svg>';
    },

    setButtonIcon(button, name, label) {
      button.innerHTML = Icons.iconMarkup(name) + '<span>' + label + '</span>';
    },

    decorateStaticButtons() {
      [1, 2, 3].forEach(number => Icons.setButtonIcon(document.getElementById('printSelected' + number), 'printer', 'พิมพ์ที่เลือก'));
    [1, 2, 3].forEach(number => {
      Icons.setButtonIcon(document.getElementById('printBox' + number), 'layers', 'พิมพ์ทั้งหมด');
      Icons.setButtonIcon(document.getElementById('clearBox' + number), 'trash', 'ล้าง Box');
      Icons.setButtonIcon(document.getElementById('searchButton' + number), 'search', 'ค้นหา');
      Icons.setButtonIcon(document.getElementById('clearSearch' + number), 'x', 'ล้าง');
    });
    }
  };


  const Label = {
    valueOf(row, aliases) {
      const keys = Object.keys(row);
    const normalized = new Map(keys.map(key => [key.toLowerCase().replace(/[^a-z0-9]/g, ''), key]));
    for (const alias of aliases) {
      const key = normalized.get(alias.toLowerCase().replace(/[^a-z0-9]/g, ''));
      if (key !== undefined && row[key] !== null && row[key] !== undefined && String(row[key]).trim() !== '') return String(row[key]).trim();
    }
    return '';
    },

    field(row, aliases) {
      return Label.valueOf(row, aliases) || '—';
    },

    labelData(row) {
      return {
      location: Label.field(row, ['Location']),
      productID: Label.field(row, ['ProductID']),
      size1: Label.valueOf(row, ['PSize1']),
      size2: Label.valueOf(row, ['PSize2']),
      size3: Label.valueOf(row, ['PSize3']),
      description: Label.field(row, ['Description']),
      barcode: Label.valueOf(row, ['BarcodeNo', 'Barcode'])
    };
    },

    makeLine(className, label, text) {
      const line = document.createElement('div');
    line.className = 'label-line ' + className;
    const caption = document.createElement('span');
    caption.className = 'field-name';
    caption.textContent = label;
    line.append(caption, document.createTextNode(text));
    return line;
    },

    makeLocationLine(location) {
      const line = document.createElement('div');
    line.className = 'label-line line-1';
    line.append(document.createTextNode('form '));
    const locationElement = document.createElement('strong');
    locationElement.textContent = location;
    line.appendChild(locationElement);
    return line;
    },

    createLabelPreview(row, boxIndex) {
      const preview = document.createElement('div');
    preview.className = 'label-preview';

    const data = Label.labelData(row);
    const isLoadingStyle = boxIndex === 1 || boxIndex === 2;
    if (isLoadingStyle) preview.classList.add('label-preview--loading');

    if (isLoadingStyle) {
      const line1 = document.createElement('div');
      line1.className = 'label-line line-1';
      line1.textContent = boxIndex === 1 ? 'ประตูรับ' : 'จ่ายหน้า Load';
      preview.appendChild(line1);
    } else {
      preview.appendChild(Label.makeLocationLine(data.location));
    }

    const productLine = document.createElement('div');
    productLine.className = 'label-line line-2';
    const productElement = document.createElement('span');
    productElement.className = 'productID';
    productElement.textContent = data.productID === '—' ? '-' : data.productID;
    const sizeElement = document.createElement('span');
    sizeElement.className = 'psize';
    sizeElement.textContent = boxIndex === 2 ? '' : [data.size1, data.size2, data.size3]
      .map(size => size && size !== '—' ? size : '9').join(' X ');
    productLine.append(productElement, sizeElement);
    preview.appendChild(productLine);
    const descriptionLine = Label.makeLine('line-3' + (isLoadingStyle ? ' line-3-loading' : ''), '', data.description);
    preview.appendChild(descriptionLine);

    // Label ประตูรับ uses the loading-label layout, but without barcode content.
    if (boxIndex !== 1) {
    const barcodeLine = document.createElement('div');
    barcodeLine.className = 'label-line line-4' + (boxIndex === 2 ? ' barcode-line-repack' : '');
    const barcodeBars = document.createElement('div');
    barcodeBars.className = 'barcode-bars';
    const cleanBarcode = data.barcode.replace(/^\*+|\*+$/g, '');
    barcodeBars.textContent = '*' + cleanBarcode + '*';
    barcodeLine.appendChild(barcodeBars);

    preview.appendChild(barcodeLine);

    const barcodeNumberLine = document.createElement('div');
    barcodeNumberLine.className = 'label-line line-5 barcode-number' + (boxIndex === 2 ? ' barcode-number-repack' : '');
    barcodeNumberLine.textContent = cleanBarcode;
    preview.appendChild(barcodeNumberLine);
    }

    if (isLoadingStyle) {
      const footer = document.createElement('div');
      footer.className = 'label-footer';
      const date = document.createElement('span');
      const today = new Date();
      date.textContent = [today.getDate(), today.getMonth() + 1, today.getFullYear()]
        .map(part => String(part).padStart(2, '0')).join('/');
      const runNumber = document.createElement('span');
      runNumber.className = 'run-number';
      runNumber.textContent = '1/1';
      footer.append(date, runNumber);
      preview.appendChild(footer);
    }

    return preview;
    },

    itemKey(row) {
      const data = Label.labelData(row);
    return [data.location, data.productID, data.barcode].join('|');
    }
  };


  const Search = {
    addTableMessage(boxIndex, message, error = false) {
      const results = boxSearches[boxIndex].results;
    results.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 6;
    cell.className = 'table-message' + (error ? ' error' : '');
    cell.textContent = message;
    row.appendChild(cell);
    results.appendChild(row);
    },

    renderSearchResults(boxIndex) {
      const state = boxSearches[boxIndex];
    state.results.replaceChildren();
    if (!state.data.length) {
      Search.addTableMessage(boxIndex, state.input.value.trim() ? 'ไม่พบ Product ID นี้' : 'กรอก Product ID เพื่อเริ่มค้นหา');
      return;
    }
    state.data.forEach(row => {
      const data = Label.labelData(row);
      const tr = document.createElement('tr');
      [data.location, data.productID, [data.size1, data.size2, data.size3].filter(Boolean).join(' × ') || '—', data.description, data.barcode || '—'].forEach(value => {
        const td = document.createElement('td'); td.textContent = value; tr.appendChild(td);
      });
      const actionCell = document.createElement('td'); actionCell.className = 'add-actions';
      const exists = boxes[boxIndex].some(item => Label.itemKey(item) === Label.itemKey(row));
      const button = document.createElement('button'); button.type = 'button';
      button.className = exists ? 'secondary mini-button added-button' : 'mini-button add-button';
      Icons.setButtonIcon(button, exists ? 'check' : 'plus', exists ? 'เพิ่มแล้ว' : 'เพิ่มลง ' + boxNames[boxIndex]); button.disabled = exists;
      button.addEventListener('click', () => Boxes.addToBox(row, boxIndex));
      actionCell.appendChild(button); tr.appendChild(actionCell); state.results.appendChild(tr);
    });
    },

    async searchByProductID(boxIndex) {
      const state = boxSearches[boxIndex], requestId = ++state.request, productID = state.input.value.trim();
    if (!productID) {
      state.data = []; Search.renderSearchResults(boxIndex); count.textContent = 'พร้อมค้นหา'; updated.textContent = 'กรอก Product ID เพื่อค้นหา'; return;
    }
    Search.addTableMessage(boxIndex, 'กำลังค้นหา Product ID…'); state.button.disabled = true;
    try {
      let payload;
      try {
        payload = await Api.searchLabels(productID);
      } catch (error) {
        if (requestId !== state.request) return;
        if (error.status === 404) state.data = [];
        else throw error;
      }
      if (requestId !== state.request) return;
      if (payload) state.data = Array.isArray(payload.data) ? payload.data : [];
      Search.renderSearchResults(boxIndex);
      count.textContent = 'Box ' + (boxIndex + 1) + ': ผลการค้นหา ' + state.data.length.toLocaleString() + ' รายการ';
      updated.textContent = 'ค้นหา Product ID: ' + productID + ' · ' + new Date().toLocaleString('th-TH');
    } catch (error) {
      if (requestId !== state.request) return;
      Search.addTableMessage(boxIndex, error.message, true);
    } finally { if (requestId === state.request) state.button.disabled = false; }
    }
  };


  const Boxes = {
    async addToBox(row, boxIndex) {
      if (boxes[boxIndex].some(item => Label.itemKey(item) === Label.itemKey(row))) return;
    const barcode = Label.valueOf(row, ['BarcodeNo', 'Barcode']);
    if (!barcode) {
      updated.textContent = 'ไม่สามารถเพิ่มได้: รายการนี้ไม่มี Barcode';
      return;
    }
    try {
      await Api.updatePrintList({ action: 'insert', barcode, category: boxNames[boxIndex] });
      boxes[boxIndex].push(row);
      Boxes.renderBoxes();
      Search.renderSearchResults(boxIndex);
      updated.textContent = 'เพิ่ม Barcode เข้า Box ' + (boxIndex + 1) + ' แล้ว';
    } catch (error) {
      updated.textContent = 'เพิ่มรายการไม่สำเร็จ: ' + error.message;
    }
    },

    renderBoxes() {
      boxes.forEach((items, boxIndex) => {
      const number = boxIndex + 1;
      const container = document.getElementById('box' + number);
      const section = container.closest('.queue-section');
      section.classList.toggle('active-box', boxIndex === activeBox);
      section.setAttribute('role', 'tabpanel');
      section.setAttribute('aria-hidden', String(boxIndex !== activeBox));
      const boxCount = document.getElementById('box' + number + 'Count');
      const clearButton = document.getElementById('clearBox' + number);
      const printButton = document.getElementById('printBox' + number);
      boxCount.textContent = items.length.toLocaleString();
      clearButton.disabled = items.length === 0;
      printButton.disabled = items.length === 0;
      container.replaceChildren();

      if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'empty';
        empty.textContent = 'ยังไม่มีรายการใน Box ' + number;
        container.appendChild(empty);
        return;
      }

      items.forEach((item, itemIndex) => {
        const card = document.createElement('article');
        card.className = 'item queue-item';
        card.dataset.box = String(boxIndex);
        card.dataset.itemIndex = String(itemIndex);
        const head = document.createElement('div');
        head.className = 'item-head';
        const label = document.createElement('span');
        label.className = 'item-index';
        label.textContent = '# ' + String(itemIndex + 1).padStart(2, '0');
        const selectionKey = boxIndex + '::' + Label.itemKey(item);
        const selectWrap = document.createElement('label');
        selectWrap.className = 'label-select';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = selectedLabels.has(selectionKey);
        checkbox.setAttribute('aria-label', 'เลือก #' + (itemIndex + 1) + ' สำหรับพิมพ์');
        checkbox.addEventListener('change', () => {
          if (checkbox.checked) selectedLabels.add(selectionKey);
          else selectedLabels.delete(selectionKey);
          Print.updatePrintSelectionUI();
        });
        selectWrap.append(checkbox, document.createTextNode('เลือกพิมพ์'));
        const quantityWrap = document.createElement('label');
        quantityWrap.className = 'print-quantity-wrap';
        quantityWrap.appendChild(document.createTextNode('จำนวนใบ'));
        const quantityInput = document.createElement('input');
        quantityInput.className = 'print-quantity';
        quantityInput.type = 'number';
        quantityInput.min = '1';
        quantityInput.max = '500';
        quantityInput.step = '1';
        quantityInput.value = '1';
        quantityInput.inputMode = 'numeric';
        quantityInput.setAttribute('aria-label', 'จำนวนใบสำหรับ Barcode ' + Label.labelData(item).barcode);
        quantityWrap.appendChild(quantityInput);
        const printButton = document.createElement('button');
        printButton.type = 'button';
        printButton.className = 'label-action print-label-button';
        printButton.innerHTML = Icons.iconMarkup('printer');
        printButton.setAttribute('aria-label', 'พิมพ์ฉลาก');
        printButton.title = 'พิมพ์ฉลาก';
        printButton.addEventListener('click', () => Print.printSingleLabel(boxIndex, itemIndex));
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'remove-button';
        remove.innerHTML = Icons.iconMarkup('trash');
        remove.setAttribute('aria-label', 'ลบฉลาก');
        remove.title = 'ลบฉลาก';
        remove.addEventListener('click', async () => {
          const barcode = Label.valueOf(item, ['BarcodeNo', 'Barcode']);
          if (!barcode) {
            updated.textContent = 'ไม่สามารถลบได้: ไม่พบ Barcode';
            return;
          }
          remove.disabled = true;
          try {
            await Api.updatePrintList({ action: 'delete', barcode, category: boxNames[boxIndex] });
            selectedLabels.delete(boxIndex + '::' + Label.itemKey(item));
            boxes[boxIndex].splice(itemIndex, 1);
            Boxes.renderBoxes();
            Search.renderSearchResults(boxIndex);
            updated.textContent = 'ลบ Barcode ออกจาก Box ' + (boxIndex + 1) + ' แล้ว';
          } catch (error) {
            updated.textContent = 'ลบรายการไม่สำเร็จ: ' + error.message;
            remove.disabled = false;
          }
        });
        head.append(label, quantityInput.parentElement, selectWrap, printButton, remove);
        card.append(head, Label.createLabelPreview(item, boxIndex));
        container.appendChild(card);
      });
    });
    Print.updatePrintSelectionUI();
    boxTabs.replaceChildren();
    boxes.forEach((items, boxIndex) => {
      const tab = document.createElement('button');
      tab.type = 'button';
      tab.className = 'box-tab' + (boxIndex === activeBox ? ' active' : '');
      tab.setAttribute('role', 'tab');
      tab.setAttribute('aria-selected', String(boxIndex === activeBox));
      tab.innerHTML = Icons.iconMarkup('box') + '<span>' + boxNames[boxIndex] + ' (' + items.length + ')</span>';
      tab.addEventListener('click', () => { activeBox = boxIndex; Boxes.renderBoxes(); });
      boxTabs.appendChild(tab);
    });

    }
  };


  const Print = {
    async printAfterFontsReady() {
      try {
      if (document.fonts && document.fonts.load) {
        await document.fonts.load('55px "LibreBarcode39"');
        await document.fonts.ready;
      }
    } catch (error) {
      console.warn('Barcode font could not be loaded before printing:', error);
    }
    window.print();
    },

    printSingleLabel(boxIndex, itemIndex) {
    if (!boxes[boxIndex] || !boxes[boxIndex][itemIndex]) return;
    Print.printQueue(boxIndex, false, itemIndex);
    },

    updatePrintSelectionUI() {
      [0, 1, 2].forEach(boxIndex => {
      const number = boxIndex + 1;
      const count = boxes[boxIndex].filter(item => selectedLabels.has(boxIndex + '::' + Label.itemKey(item))).length;
      document.getElementById('selectedCount' + number).textContent = count.toLocaleString();
      const button = printSelectedButtons[boxIndex];
      button.disabled = count === 0;
      Icons.setButtonIcon(button, 'printer', 'พิมพ์ที่เลือก');
    });
    },

    printQueue(boxIndex = null, selectedOnly = false, singleItemIndex = null) {
    const cards = [...document.querySelectorAll('.queue-item')];
    const printableCards = [];
    cards.forEach(card => {
      card.classList.remove('print-target', 'print-last');
      if (card.dataset.printClone === 'true') card.remove();
    });

    cards.filter(card => card.isConnected).forEach(card => {
      const cardBox = Number(card.dataset.box);
      const itemIndex = Number(card.dataset.itemIndex);
      const item = boxes[cardBox]?.[itemIndex];
      const key = item ? cardBox + '::' + Label.itemKey(item) : '';
      const inRequestedBox = boxIndex === null || cardBox === boxIndex;
      const isRequestedItem = singleItemIndex === null || itemIndex === singleItemIndex;
      if (item && inRequestedBox && isRequestedItem && (!selectedOnly || selectedLabels.has(key))) {
        printableCards.push(card);
      }
    });

    if (printableCards.length === 0) return;
    for (const card of printableCards) {
      const cardBox = Number(card.dataset.box);
      const input = card.querySelector('.print-quantity');
      const quantity = Number(input?.value ?? 1);
      if (!Number.isInteger(quantity) || quantity < 1 || quantity > 500) {
        updated.textContent = 'กรุณาระบุจำนวนใบเป็นจำนวนเต็มระหว่าง 1 ถึง 500';
        input?.focus();
        return;
      }
    }

    const printCards = [];
    for (const card of printableCards) {
      const cardBox = Number(card.dataset.box);
      const quantity = Number(card.querySelector('.print-quantity')?.value ?? 1);
      for (let run = 1; run <= quantity; run++) {
        const copy = card.cloneNode(true);
        copy.dataset.printClone = 'true';
        copy.classList.add('print-target');
        copy.classList.remove('print-last');
        if (cardBox === 1 || cardBox === 2) {
          const runElement = copy.querySelector('.run-number');
          if (runElement) runElement.textContent = run + '/' + quantity;
        }

        printCards.push(copy);
        card.parentElement.appendChild(copy);
      }
    }

    if (printCards.length === 0) return;
    printCards.forEach(card => { card.classList.add('print-target'); card.classList.remove('print-last'); });
    printCards[printCards.length - 1].classList.add('print-last');
    Print.printAfterFontsReady();
    }
  };


  const Api = {
    async request(url, options = {}) {
      const response = await fetch(url, options);
      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        const error = new Error(payload.error || 'API request failed');
        error.status = response.status;
        throw error;
      }

      return payload;
    },

    searchLabels(productID) {
      const query = encodeURIComponent(productID);
      return Api.request('/api/labels/search?productID=' + query);
    },

    getPrintList() {
      return Api.request('/api/label-print-list');
    },

    updatePrintList(data) {
      return Api.request('/api/label-print-list', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
    },

    checkHealth() {
      return Api.request('/api/health');
    }
  };

  const PrintList = {
    async buildData(button) {
      if (!window.confirm('ต้องการอัพเดทข้อมูล Label จากฐานข้อมูลใช่หรือไม่?')) return;

      button.disabled = true;
      updated.textContent = 'กำลังอัพเดทข้อมูล…';

      try {
        await Api.updatePrintList({ action: 'buildData' });
        await PrintList.loadPrintList();
        updated.textContent = 'อัพเดทข้อมูลสำเร็จ';
      } catch (error) {
        updated.textContent = 'อัพเดทข้อมูลไม่สำเร็จ: ' + error.message;
      } finally {
        button.disabled = false;
      }
    },

    async loadPrintList() {
      try {
        const payload = await Api.getPrintList();

        boxes.forEach(items => items.splice(0, items.length));

        (Array.isArray(payload.data) ? payload.data : []).forEach(row => {
          const category = Label.valueOf(row, ['Category']);
          const boxIndex = {
            'Label Repack': 0,
            'Label ประตูรับ': 1,
            'Label หน้าโหลด': 2
          }[category];

          if (boxIndex === undefined) return;

          const exists = boxes[boxIndex].some(
            item => Label.itemKey(item) === Label.itemKey(row)
          );

          if (!exists) boxes[boxIndex].push(row);
        });

        Boxes.renderBoxes();
        boxSearches.forEach((_, index) => Search.renderSearchResults(index));
        updated.textContent = 'โหลดรายการจากฐานข้อมูลแล้ว';
      } catch (error) {
        updated.textContent = 'โหลดรายการ Box ไม่สำเร็จ: ' + error.message;
      }
    },

    async checkConnection() {
      try {
        await Api.checkHealth();
        connection.textContent = '● SQL Server เชื่อมต่อแล้ว';
        connection.style.background = '#e1f8eb';
        connection.style.color = '#17683a';
      } catch {
        connection.textContent = 'ไม่สามารถตรวจสอบ API ได้';
        connection.style.background = '#fee4e2';
        connection.style.color = '#b42318';
      }
    }
  };

  const boxSearches = [0, 1, 2].map(index => {
    const number = index + 1;
    return { input: document.getElementById('search' + number), button: document.getElementById('searchButton' + number),
      clear: document.getElementById('clearSearch' + number), results: document.getElementById('searchResults' + number), request: 0, data: [] };
  });
  const printSelectedButtons = [1, 2, 3].map(number => document.getElementById('printSelected' + number));
  const selectedLabels = new Set();
  const connection = document.getElementById('connection');
  const count = document.getElementById('count');
  const updated = document.getElementById('updated');
  const boxes = [[], [], []];
  let activeBox = 0;
  const boxTabs = document.getElementById("boxTabs");
  const boxNames = ["Label Repack", "Label ประตูรับ", "Label หน้าโหลด"];

  const iconPaths = {
    search: '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>',
    x: '<path d="m18 6-12 12M6 6l12 12"></path>',
    printer: '<path d="M6 9V3h12v6"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v7H6z"></path>',
    trash: '<path d="M3 6h18M8 6V4h8v2m-11 0 1 15h10l1-15M10 10v7m4-7v7"></path>',
    plus: '<path d="M12 5v14M5 12h14"></path>',
    check: '<path d="m5 12 4 4L19 6"></path>',
    box: '<path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="m3 8 9 5 9-5M3 8v9l9 5 9-5V8M12 13v9"></path>',
    layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="m3 12 9 5 9-5M3 16l9 5 9-5"></path>',
    barcode: '<path d="M4 5v14M7 5v14M10 5v14M14 5v14M16 5v14M20 5v14"></path>',
    package: '<path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="M3 8v9l9 4 9-4V8M12 13v8"></path>',
    refresh: '<path d="M20 7v5h-5"></path><path d="M4 17v-5h5"></path><path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2"></path>'
  };

  window.addEventListener('afterprint', () => {
    document.querySelectorAll('.queue-item').forEach(card => {
      card.classList.remove('print-target', 'print-last');
      if (card.dataset.printClone === 'true') card.remove();
    });
  });

  boxSearches.forEach((state, boxIndex) => {
    state.input.addEventListener('keydown', event => {
      if (event.key !== 'Enter') return; event.preventDefault(); Search.searchByProductID(boxIndex);
    });
    state.button.addEventListener('click', () => Search.searchByProductID(boxIndex));
    state.clear.addEventListener('click', () => {
      state.input.value = ''; state.data = []; state.request++; state.button.disabled = false;
      Search.renderSearchResults(boxIndex); count.textContent = 'พร้อมค้นหา';
      updated.textContent = 'ล้างคำค้นหา Box ' + (boxIndex + 1) + ' แล้ว'; state.input.focus();
    });
  });
  printSelectedButtons.forEach((button, boxIndex) => button.addEventListener('click', () => Print.printQueue(boxIndex, true)));
  [0, 1, 2].forEach(index => {
    const number = index + 1;
    document.getElementById('clearBox' + number).addEventListener('click', async event => {
      const button = event.currentTarget;
      button.disabled = true;
      try {
        await Api.updatePrintList({ action: 'clearCategory', category: boxNames[index] });
        boxes[index].forEach(item => selectedLabels.delete(index + '::' + Label.itemKey(item)));
        boxes[index] = [];
        Boxes.renderBoxes();
        Search.renderSearchResults(index);
        updated.textContent = 'ล้างรายการ Box ' + number + ' แล้ว';
      } catch (error) {
        updated.textContent = 'ล้าง Box ไม่สำเร็จ: ' + error.message;
      } finally {
        button.disabled = boxes[index].length === 0;
      }
    });
    document.getElementById('printBox' + number).addEventListener('click', () => Print.printQueue(index));
  });

  const buildDataButton = document.getElementById('buildDataButton');
  buildDataButton.innerHTML = Icons.iconMarkup('refresh') + '<span>อัพเดทข้อมูล</span>';
  buildDataButton.addEventListener('click', () => PrintList.buildData(buildDataButton));

  Icons.decorateStaticButtons();
  Boxes.renderBoxes();
  boxSearches.forEach((_, index) => Search.renderSearchResults(index));
  PrintList.loadPrintList();
  PrintList.checkConnection();
})();
