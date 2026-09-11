<!-- User Management -->
<?php $currentUser = Session::get('user'); ?>

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

<style>
    .btn-primary-modern {
        background: linear-gradient(135deg, #4f7cff, #2b6cb0);
        border: none;
        color: #fff;
        border-radius: 8px;
        padding: 0.4rem 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-primary-modern:hover {
        box-shadow: 0 4px 10px rgba(43, 108, 176, 0.3);
        color: #fff;
    }

    .search-input-wrap {
        position: relative;
        max-width: 340px;
    }

    .search-input-wrap i.bi-search {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.85rem;
    }

    .search-input {
        width: 100%;
        padding: 0.5rem 2.2rem 0.5rem 2.1rem;
        border: 1px solid #eef0f2;
        border-radius: 10px;
        font-size: 0.88rem;
        background: #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
        outline: none;
        transition: border-color 0.15s ease;
    }

    .search-input:focus {
        border-color: #4f7cff;
    }

    .search-clear {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.9rem;
        text-decoration: none;
    }

    .search-clear:hover {
        color: #dc2626;
    }

    .search-spinner {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #4f7cff;
        font-size: 0.9rem;
    }

    .spin {
        animation: spin-anim 0.7s linear infinite;
    }

    @keyframes spin-anim {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .card-modern {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #eef0f2;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        position: relative;
        min-height: 100px;
    }

    .card-modern.loading {
        opacity: 0.5;
        pointer-events: none;
    }

    .table-modern thead th {
        background: #f8f9fb;
        color: #6b7280;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #eef0f2;
        padding: 0.85rem 1rem;
        white-space: nowrap;
    }

    .table-modern tbody td {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        color: #374151;
        border-bottom: 1px solid #f4f5f7;
    }

    .table-modern tbody tr:last-child td {
        border-bottom: none;
    }

    .table-modern tbody tr:hover {
        background: #f8faff;
    }

    .sort-link {
        color: #6b7280;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: color 0.15s ease;
        cursor: pointer;
    }

    .sort-link:hover {
        color: #2b6cb0;
    }

    .sort-icon-idle {
        font-size: 0.7rem;
        opacity: 0.4;
    }

    .sort-icon-active {
        font-size: 0.75rem;
        color: #4f7cff;
    }

    .user-avatar-sm {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #eef2ff;
        color: #4f7cff;
        font-weight: 700;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .badge-modern {
        display: inline-block;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .badge-admin {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-manager {
        background: #fef3c7;
        color: #b45309;
    }

    .badge-user {
        background: #e5e7eb;
        color: #4b5563;
    }

    .status-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-active {
        background: #22c55e;
    }

    .status-inactive {
        background: #9ca3af;
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #6b7280;
        transition: all 0.2s ease;
    }

    .btn-icon:hover {
        background: #eef2ff;
        color: #2b6cb0;
    }

    .btn-icon-danger:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .pagination-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.9rem 1.2rem;
        border-top: 1px solid #f4f5f7;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .pagination-info {
        font-size: 0.82rem;
        color: #9ca3af;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 0.5rem;
        border-radius: 8px;
        color: #6b7280;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 500;
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .page-btn:hover {
        background: #eef2ff;
        color: #2b6cb0;
    }

    .page-btn.active {
        background: linear-gradient(135deg, #4f7cff, #2b6cb0);
        color: #fff;
    }

    .page-btn.disabled {
        opacity: 0.35;
        pointer-events: none;
    }

    .page-dots {
        color: #9ca3af;
        font-size: 0.82rem;
        padding: 0 0.2rem;
    }
</style>

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
                .then(res => res.text())
                .then(html => {
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