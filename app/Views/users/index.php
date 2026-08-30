<!-- User Management -->
<?php $currentUser = Session::get('user'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">จัดการผู้ใช้งาน</h4>
        <span class="text-muted small">ทั้งหมด <?= count($users) ?> รายการ</span>
    </div>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/users/create" class="btn btn-primary-modern btn-sm">
            <i class="bi bi-plus-lg"></i> เพิ่มผู้ใช้
        </a>
    <?php endif; ?>
</div>

<div class="card-modern">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-modern">
            <thead>
                <tr>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>อีเมล</th>
                    <th>แผนก</th>
                    <th>ตำแหน่ง</th>
                    <th>สิทธิ์</th>
                    <th>สถานะ</th>
                    <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar-sm">
                                    <?= strtoupper(mb_substr($u['first_name'], 0, 1)) ?>
                                </span>
                                <span class="fw-medium"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></span>
                            </div>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['svp_department_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($u['position_name'] ?? '-') ?></td>
                        <td>
                            <span class="badge-modern badge-<?= $u['user_role'] ?>">
                                <?= htmlspecialchars($u['user_role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="status-dot status-active"></span><span class="text-success small fw-medium">ใช้งาน</span>
                            <?php else: ?>
                                <span class="status-dot status-inactive"></span><span class="text-muted small">ปิดการใช้งาน</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($currentUser['user_role'] === 'admin'): ?>
                            <td class="text-end">
                                <a href="<?= APP_URL ?>/users/<?= $u['svp_user_id'] ?>/edit" class="btn-icon" title="แก้ไข">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="<?= APP_URL ?>/users/<?= $u['svp_user_id'] ?>/delete" method="POST" class="d-inline"
                                    onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้?');">
                                    <button type="submit" class="btn-icon btn-icon-danger" title="ลบ">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                            ยังไม่มีข้อมูลผู้ใช้
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
</style>