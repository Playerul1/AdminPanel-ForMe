<?php
$err = Session::flash('error');
$ok  = Session::flash('ok');

$countries = $countries ?? [];
$sources   = $sources ?? [];
$types     = $types ?? [];
$statuses  = $statuses ?? [];
$defaultStatusId = (int)($defaultStatusId ?? 0);

$old = fn($k, $d='') => (string)ssa_old($k, $d);
$oldInt = fn($k, $d=0) => (int)ssa_old($k, $d);
?>
<div class="ssa-fade-in">

    <div class="page-head">
        <div>
            <h1>➕ Adaugă Lead</h1>
            <div class="muted">Completează informațiile. Minimul necesar: nume (afacere/fondator), telefon, țara, sursa.</div>
        </div>
        <div class="page-actions">
            <a class="btn" href="<?= ssa_base_url('leads') ?>">⬅️ Înapoi la Lead-uri</a>
        </div>
    </div>

    <?php if ($err): ?>
        <div class="toast bad" style="margin-bottom:12px"><?= ssa_e((string)$err) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
        <div class="toast ok" style="margin-bottom:12px"><?= ssa_e((string)$ok) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= ssa_base_url('leads/create') ?>" enctype="multipart/form-data">
        <div class="grid" style="grid-template-columns: 1fr; gap:14px">

            <div class="panel hover-lift">
                <div class="panel-head">
                    <div>
                        <div class="panel-title">Informații de bază</div>
                        <div class="panel-sub">Nume, telefon, țară, adresă, website.</div>
                    </div>
                </div>

                <div class="form-grid two">
                    <div class="field">
                        <label>Numele afacerii</label>
                        <input class="input" name="business_name" value="<?= ssa_e($old('business_name')) ?>" placeholder="ex: SmartSoftArt SRL">
                    </div>

                    <div class="field">
                        <label>Numele Prenumele fondatorului</label>
                        <input class="input" name="founder_name" value="<?= ssa_e($old('founder_name')) ?>" placeholder="ex: Cristian Șoltoianu">
                    </div>
                </div>

                <div class="form-grid two" style="margin-top:10px">
                    <div class="field">
                        <label>Număr de telefon *</label>
                        <input class="input" name="phone" value="<?= ssa_e($old('phone')) ?>" placeholder="+373 ... / +40 ...">
                    </div>

                    <div class="field">
                        <label>Website</label>
                        <input class="input" name="website" value="<?= ssa_e($old('website')) ?>" placeholder="https://...">
                    </div>
                </div>

                <div class="form-grid three" style="margin-top:10px">
                    <div class="field">
                        <label>Țara *</label>
                        <select name="country_id">
                            <option value="0">Selectează</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $oldInt('country_id')) ? 'selected' : '' ?>>
                                    <?= ssa_e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Oraș</label>
                        <input class="input" name="city" value="<?= ssa_e($old('city')) ?>" placeholder="Oraș">
                    </div>

                    <div class="field">
                        <label>Județ / Raion</label>
                        <input class="input" name="county" value="<?= ssa_e($old('county')) ?>" placeholder="Județ / Raion">
                    </div>
                </div>

                <div class="field" style="margin-top:10px">
                    <label>Adresa completă</label>
                    <textarea name="address" rows="3" placeholder="Strada, nr, etc..."><?= ssa_e($old('address')) ?></textarea>
                </div>
            </div>

            <div class="panel hover-lift">
                <div class="panel-head">
                    <div>
                        <div class="panel-title">Încadrare lead</div>
                        <div class="panel-sub">Tip afacere, sursă, status, asignare.</div>
                    </div>
                </div>

                <div class="form-grid three">
                    <div class="field">
                        <label>Tip de afacere</label>
                        <select name="business_type_id">
                            <option value="0">Selectează</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= (int)$t['id'] ?>" <?= ((int)$t['id'] === $oldInt('business_type_id')) ? 'selected' : '' ?>>
                                    <?= ssa_e($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Sursa Lead *</label>
                        <select name="source_id">
                            <option value="0">Selectează</option>
                            <?php foreach ($sources as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= ((int)$s['id'] === $oldInt('source_id')) ? 'selected' : '' ?>>
                                    <?= ssa_e($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Status Lead</label>
                        <select name="status_id">
                            <option value="0">Default</option>
                            <?php foreach ($statuses as $s): ?>
                                <?php
                                $sid = (int)$s['id'];
                                $selected = $oldInt('status_id', $defaultStatusId) === $sid;
                                ?>
                                <option value="<?= $sid ?>" <?= $selected ? 'selected' : '' ?>>
                                    <?= ssa_e($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help">Dacă alegi “Default”, se pune statusul default din setări.</div>
                    </div>
                </div>

                <div class="form-grid two" style="margin-top:10px">
                    <div class="field">
                        <label>Asignat (ID staff) — temporar</label>
                        <input class="input" name="assigned_to" value="<?= ssa_e($old('assigned_to')) ?>" placeholder="ex: 3">
                        <div class="help">După ce legăm WHMCS staff, aici va fi dropdown cu angajați.</div>
                    </div>

                    <div class="field">
                        <label>Număr înregistrare afacere</label>
                        <input class="input" name="company_reg_no" value="<?= ssa_e($old('company_reg_no')) ?>" placeholder="ex: J40/... / IDNO ...">
                    </div>
                </div>
            </div>

            <div class="panel hover-lift">
                <div class="panel-head">
                    <div>
                        <div class="panel-title">Social Media</div>
                        <div class="panel-sub">Facebook / Instagram / LinkedIn / WhatsApp / Telegram.</div>
                    </div>
                </div>

                <div class="form-grid two">
                    <div class="field">
                        <label>Facebook</label>
                        <input class="input" name="social_facebook" value="<?= ssa_e($old('social_facebook')) ?>" placeholder="link / username">
                    </div>
                    <div class="field">
                        <label>Instagram</label>
                        <input class="input" name="social_instagram" value="<?= ssa_e($old('social_instagram')) ?>" placeholder="link / username">
                    </div>
                </div>

                <div class="form-grid two" style="margin-top:10px">
                    <div class="field">
                        <label>LinkedIn</label>
                        <input class="input" name="social_linkedin" value="<?= ssa_e($old('social_linkedin')) ?>" placeholder="link">
                    </div>
                    <div class="field">
                        <label>WhatsApp</label>
                        <input class="input" name="social_whatsapp" value="<?= ssa_e($old('social_whatsapp')) ?>" placeholder="+.. / link wa.me">
                    </div>
                </div>

                <div class="field" style="margin-top:10px">
                    <label>Telegram</label>
                    <input class="input" name="social_telegram" value="<?= ssa_e($old('social_telegram')) ?>" placeholder="@username / link">
                </div>
            </div>

            <div class="panel hover-lift">
                <div class="panel-head">
                    <div>
                        <div class="panel-title">Imagine + extra</div>
                        <div class="panel-sub">Logo/poza afacere + alte clasificări + descriere.</div>
                    </div>
                </div>

                <div class="form-grid two">
                    <div class="field">
                        <label>Poza (logo / profil / imagine afacere)</label>
                        <input class="input" type="file" name="photo" accept="image/png,image/jpeg,image/webp">
                        <div class="help">Max 5MB • JPG/PNG/WEBP</div>
                    </div>

                    <div class="field">
                        <label>Alte clasificări (pentru unele țări)</label>
                        <textarea name="extra_text" rows="4" placeholder="Ex:
Cod CAEN: 6201
Categorie: IT
Observații: ..."><?= ssa_e($old('extra_text')) ?></textarea>
                        <div class="help">Format recomandat: <b>Cheie: Valoare</b> (una pe rând).</div>
                    </div>
                </div>

                <div class="field" style="margin-top:10px">
                    <label>Descriere</label>
                    <textarea name="description" rows="4" placeholder="Descriere lead / ce dorește clientul..."><?= ssa_e($old('description')) ?></textarea>
                </div>
            </div>

            <div class="panel">
                <div class="page-actions" style="justify-content:flex-end">
                    <a class="btn btn-ghost" href="<?= ssa_base_url('leads') ?>">Anulează</a>
                    <button class="btn btn-primary btn-shimmer" type="submit">✅ Salvează Lead</button>
                </div>
            </div>

        </div>
    </form>

</div>
