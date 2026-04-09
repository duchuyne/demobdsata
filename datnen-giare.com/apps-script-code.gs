// ============================================================
// GOOGLE APPS SCRIPT – Nhận dữ liệu form → Google Sheet
// Sheet: https://docs.google.com/spreadsheets/d/1FEHLWYhtzjGhq_tc6YjrNCbvaPDefNMjCWm-ZT08TH8
// ============================================================
// CÁCH CÀI ĐẶT:
// 1. Mở Google Sheet trên → Extensions → Apps Script
// 2. Xóa code cũ, dán toàn bộ code dưới này vào
// 3. Bấm "Save" (Ctrl+S)
// 4. Bấm "Deploy" → "New deployment"
//    - Type: Web app
//    - Execute as: Me (your account)
//    - Who has access: Anyone
// 5. Bấm "Deploy" → Copy URL (dạng: https://script.google.com/macros/s/ABC.../exec)
// 6. Dán URL vào file index.html, thay "APPS_SCRIPT_ID" trong biến SCRIPT_URL
// ============================================================

var SHEET_NAME = 'Sheet1'; // Tên tab trong Google Sheet (mặc định là Sheet1)

function doGet(e) {
  return handleRequest(e);
}

function doPost(e) {
  return handleRequest(e);
}

function handleRequest(e) {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(SHEET_NAME) || ss.getSheets()[0];

    // Tạo header nếu sheet còn trống
    if (sheet.getLastRow() === 0) {
      sheet.appendRow(['Thời Gian', 'Họ Và Tên', 'Số Điện Thoại', 'Tài Chính']);
      // Format header
      var headerRange = sheet.getRange(1, 1, 1, 4);
      headerRange.setFontWeight('bold');
      headerRange.setBackground('#09353f');
      headerRange.setFontColor('#f5c842');
      // Format cột Số Điện Thoại (cột C) thành Plain Text để giữ số 0 đầu
      sheet.getRange('C:C').setNumberFormat('@STRING@');
    } else {
      // Nếu sheet cũ đang dùng header Email thì đổi sang Tài Chính cho đồng bộ form mới.
      var oldHeader = sheet.getRange(1, 4).getValue();
      if (oldHeader === 'Email') {
        sheet.getRange(1, 4).setValue('Tài Chính');
      }
    }

    // Lấy dữ liệu từ request params
    var params = e.parameter || {};
    var name   = params.name  || '';
    var phone  = params.phone || '';
    var finance = params.finance || params.email || '';
    var time   = params.time  || new Date().toLocaleString('vi-VN', {timeZone: 'Asia/Ho_Chi_Minh'});

    // Ghi vào Sheet – thêm ký tự apostrophe trước SĐT để Google Sheets giữ số 0 đầu
    sheet.appendRow([time, name, "'" + phone, finance]);

    // Format dòng mới: highlight dòng chẵn
    var lastRow = sheet.getLastRow();
    if (lastRow % 2 === 0) {
      sheet.getRange(lastRow, 1, 1, 4).setBackground('#f0f4f2');
    }

    // Auto-resize columns
    sheet.autoResizeColumns(1, 4);

    return ContentService
      .createTextOutput(JSON.stringify({status: 'ok', row: lastRow}))
      .setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService
      .createTextOutput(JSON.stringify({status: 'error', message: err.toString()}))
      .setMimeType(ContentService.MimeType.JSON);
  }
}
