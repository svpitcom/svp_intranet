<!-- User Management -->
<?php $currentUser = Session::get('user'); ?>

<?php if (Session::get('success')): ?><div class="alert-modern alert-success-modern"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars(Session::get('success')) ?></div><?php endif; ?>
<?php if (Session::get('error')): ?><div class="alert-modern alert-danger-modern"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars(Session::get('error')) ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">จัดการผู้ใช้งาน</h4>
        <span class="text-muted small" id="user-count-label">ทั้งหมด <?= number_format($totalUsers) ?> รายการ</span>
    </div>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/users/create" class="btn btn-primary-modern btn-sm">
            <i class="bi bi-plus-lg"></i> เพิ่มผู้ใช้
        </a>
    <?php endif; ?>
</div>

<?php if ($currentUser['user_role'] === 'admin'): ?>
<div class="card-modern p-3 mb-3">
    <form method="post" action="<?= APP_URL ?>/users/sync-excel" class="d-inline" onsubmit="return confirm('อัปเดตไฟล์ Excel DEMO ด้วยรายชื่อทั้งหมดจาก Intranet หรือไม่? ข้อมูลที่แก้ไว้ใน Excel จะถูกแทนที่');">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-outline-primary" type="submit">อัปเดต Excel ทดสอบบน SharePoint</button>
    </form>
    <form method="post" action="<?= APP_URL ?>/users/import-excel" class="d-inline ms-2" onsubmit="return confirm('นำการแก้ไขจาก Excel มาอัปเดตบัญชีผู้ใช้งานเดิมใน Intranet หรือไม่?');">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-outline-success" type="submit">นำเข้าการแก้ไขจาก Excel</button>
    </form>
    <p class="small text-muted mt-2 mb-0">ครั้งแรกกดอัปเดตเพื่อสร้างไฟล์ <?= htmlspecialchars(SharePointClient::USER_DEMO_NAME, ENT_QUOTES, 'UTF-8') ?> ใน SharePoint ก่อน ไฟล์มีข้อมูลบัญชีแต่ไม่มีรหัสผ่าน การนำเข้าปรับปรุงเฉพาะ ID ที่มีอยู่; เพิ่มบัญชีใหม่ผ่านปุ่ม “เพิ่มผู้ใช้” และลบแถวใน Excel จะไม่ลบบัญชีในระบบ</p>
</div>
<?php endif; ?>

<!-- ===== Search box (realtime) ===== -->
<div class="search-box mb-3">
    <div class="search-input-wrap">
        <i class="bi bi-search"></i>
        <input type="text" id="user-search-input" class="search-input"
            placeholder="ค้นหาชื่อ, ชื่อผู้ใช้ หรืออีเมล..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        <a href="#" id="user-search-clear" class="search-clear <?= $search === '' ? 'd-none' : '' ?>" title="ล้างการค้นหา">
            <i class="bi bi-x-circle-fill"></i>
        </a>
        <span id="user-search-spinner" class="search-spinner d-none">
            <i class="bi bi-arrow-repeat spin"></i>
        </span>
    </div>
</div>

<div class="card-modern">
    <div id="user-table-container"
        data-sort="<?= htmlspecialchars($sort) ?>"
        data-dir="<?= htmlspecialchars($dir) ?>"
        data-page="<?= $currentPage ?>">
        <?php require __DIR__ . '/_table.php'; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('user-search-input');
        const clearBtn = document.getElementById('user-search-clear');
        const spinner = document.getElementById('user-search-spinner');
        const tableContainer = document.getElementById('user-table-container');
        const cardModern = tableContainer.closest('.card-modern');
        const countLabel = document.getElementById('user-count-label');

        let debounceTimer = null;
        let currentRequest = null;

        function loadUsers(params) {
            // ยกเลิก request เก่าถ้ายังไม่เสร็จ กันผลลัพธ์เก่ามาทับผลลัพธ์ใหม่ (race condition)
            if (currentRequest) currentRequest.abort();

            const controller = new AbortController();
            currentRequest = controller;

            spinner.classList.remove('d-none');
            cardModern.classList.add('loading');

            const query = new URLSearchParams(params).toString();

            fetch('<?= APP_URL ?>/users/search?' + query, {
                    signal: controller.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (res.redirected) { window.location.assign(res.url); throw new Error('Session expired'); }
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(html => {
                    if (currentRequest !== controller) return;
                    tableContainer.innerHTML = html;
                    tableContainer.dataset.sort = params.sort;
                    tableContainer.dataset.dir = params.dir;
                    tableContainer.dataset.page = params.page;

                    // อัปเดตจำนวนรายการที่หัวข้อด้วย (ดึงจาก data attribute ที่แนบมากับ response)
                    const totalMatch = html.match(/data-total-count="(\d+)"/);
                    if (totalMatch) {
                        countLabel.textContent = 'ทั้งหมด ' + Number(totalMatch[1]).toLocaleString() + ' รายการ';
                    }

                    clearBtn.classList.toggle('d-none', searchInput.value.trim() === '');
                })
                .catch(err => {
                    if (err.name !== 'AbortError') console.error('Search error:', err);
                })
                .finally(() => {
                    if (currentRequest !== controller) return;
                    spinner.classList.add('d-none');
                    cardModern.classList.remove('loading');
                    currentRequest = null;
                });
        }

        function triggerSearch(page = 1) {
            loadUsers({
                search: searchInput.value.trim(),
                sort: tableContainer.dataset.sort,
                dir: tableContainer.dataset.dir,
                page: page
            });
        }

        // ---- Debounce: รอ 400ms หลังหยุดพิมพ์ค่อยยิง request ----
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => triggerSearch(1), 400);
        });

        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            searchInput.value = '';
            triggerSearch(1);
            searchInput.focus();
        });

        // ---- Event delegation: sort link และ pagination ถูกสร้างใหม่ทุกครั้งที่ AJAX โหลดตาราง ----
        tableContainer.addEventListener('click', function(e) {
            const sortLink = e.target.closest('.js-sort-link, .sort-link');
            const pageLink = e.target.closest('.js-page-link');

            if (sortLink && sortLink.dataset.sort) {
                e.preventDefault();
                tableContainer.dataset.sort = sortLink.dataset.sort;
                tableContainer.dataset.dir = sortLink.dataset.dir;
                triggerSearch(1);
            }

            if (pageLink && !pageLink.classList.contains('disabled')) {
                e.preventDefault();
                triggerSearch(pageLink.dataset.page);
            }
        });
    });
</script>
