# G470 security plugin

Custom security plugin

### 3. How to Use

4. **Configure** the restriction:  
   * Go to **Settings → REST Users Protect**.  
   * Check/uncheck *Enable Restriction*.  
   * Enter the capability you want to allow (defaults to `list_users`).  
   * Click **Save Changes**.
5. Test:  
   * As a non‑logged‑in user or with insufficient capability → `401 Unauthorized` / `403 Forbidden`.  
   * As a user with the required capability → endpoint works normally.

---

### 4. Customizations

| Need | How to change |
|------|---------------|
| **Different capability** | Edit the **Required Capability** field in the settings page. |
| **Add more restriction rules** | Hook into `rest_pre_dispatch` and extend the logic. |
| **Translate the plugin** | Create a `languages/` folder, run `makepot` or `poedit` on the file, then load `load_plugin_textdomain()` (already included via the header). |
| **Persist settings in a separate table** | Replace the single `rup_options` option with a custom table and adjust the `register_setting` logic. |

---

### 5. Security Checklist

* ✅ Uses **`current_user_can()`** for capability checks.  
* ✅ Stores all settings in a **single option** (`rup_options`) – no sensitive data.  
* ✅ Validates/sanitizes input via the **Settings API**.  
* ✅ Protects endpoint even if some other plugin had previously removed the permission check.  

---

### 6. What If I Don’t Want the Settings Page?

If you just want the hard‑coded restriction, delete the entire Settings‑related section of the code and keep the `rup_rest_pre_dispatch` filter. But the plugin above gives you a convenient UI to toggle it on‑the‑fly.
