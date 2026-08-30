<?php $currentUser = Session::get('user'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">จัดการแผนก</h4>
        <span class="text-muted small">ทั้งหมด <?= count($departments) ?> รายการ</span>
    </div>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/departments/create" class="btn-primary-modern btn-sm">
            <i class="bi bi-plus-lg"></i> เพิ่มแผนก
        </a>
    <?php endif; ?>
</div>

<div class="card-modern">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-modern">
            <thead>
                <tr>
                    <th>รหัสแผนก</th>
                    <th>ชื่อแผนก</th>
                    <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $d): ?>
                    <tr>
                        <td>
                            <span class="code-chip"><?= htmlspecialchars($d['svp_code_department'] ?? '-') ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="dept-icon"><i class="bi bi-diagram-3-fill"></i></span>
                                <span class="fw-medium"><?= htmlspecialchars($d['svp_department_name']) ?></span>
                            </div>
                        </td>
                        <?php if ($currentUser['user_role'] === 'admin'): ?>
                            <td class="text-end">
                                <a href="<?= APP_URL ?>/departments/<?= $d['svp_department_id'] ?>/edit" class="btn-icon" title="แก้ไข">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="<?= APP_URL ?>/departments/<?= $d['svp_department_id'] ?>/delete" method="POST" class="d-inline"
                                    onsubmit="return confirm('ยืนยันการลบแผนกนี้?');">
                                    <button type="submit" class="btn-icon btn-icon-danger" title="ลบ">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                            ยังไม่มีข้อมูลแผนก
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-primary-modern:hover {
        box-shadow: 0 4px 10px rgba(43, 108, 176, 0.3);
        color: #fff;
    }

    .card-modern {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #eef0f2;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        overflow: hidden;
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

    .code-chip {
        display: inline-block;
        background: #f3f4f6;
        color: #4b5563;
        font-family: 'SFMono-Regular', Consolas, monospace;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
    }

    .dept-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #eef2ff;
        color: #4f7cff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
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
</style>