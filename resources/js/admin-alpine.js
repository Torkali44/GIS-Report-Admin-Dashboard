/**
 * Admin Alpine components — must load before Alpine.start()
 */

// ── helpers ──────────────────────────────────────────────────────────────────

function _loadCatsFromDom() {
    const el = document.getElementById('categories-json-data');
    if (!el) return [];
    try {
        const arr = JSON.parse(el.textContent.trim());
        // deduplicate by name, keep first occurrence (lowest ID)
        const seen = new Set();
        return arr.filter(c => {
            if (!c || !c.name || seen.has(c.name)) return false;
            seen.add(c.name);
            return true;
        });
    } catch (e) {
        console.error('[admin-alpine] categories-json parse error:', e);
        return [];
    }
}

function _loadSectionsFromDom() {
    const el = document.getElementById('ready-sections-json-data');
    if (!el) return [];
    try {
        return JSON.parse(el.textContent.trim()) || [];
    } catch (e) {
        console.error('[admin-alpine] ready-sections-json parse error:', e);
        return [];
    }
}

/**
 * Find a NoteCategory by numeric ID string OR exact name.
 */
function _catById(cats, id) {
    if (!id && id !== 0) return null;
    return cats.find(c => String(c.id) === String(id)) || null;
}

// ── areaForm ─────────────────────────────────────────────────────────────────

export function registerAdminAlpine(Alpine) {

    Alpine.data('areaForm', () => ({
        // section picker
        selectedSection: '',   // value from <select> (a section name or '__custom__')
        customSection: '',     // free-text input when selectedSection === '__custom__'
        sectionNameEn: '',

        // note lists
        notesList: [],
        recommendationsList: [],
        notesText: '',
        recommendationsText: '',
        notesListEn: [],
        recommendationsListEn: [],
        notesTextEn: '',
        recommendationsTextEn: '',

        // category picker state
        allCategories: [],
        readySections: [],
        selectedCategory: '',    // numeric ID as string
        categoryNotes: [],
        categoryRecs: [],
        selectedNotes: [],
        selectedRecsArr: [],

        // ── lifecycle ─────────────────────────────────────────────────────────
        init() {
            this.allCategories = _loadCatsFromDom();
            this.readySections = _loadSectionsFromDom();
        },

        // ── section name ──────────────────────────────────────────────────────
        get resolvedSectionName() {
            if (this.selectedSection === '__custom__') return this.customSection || '';
            return this.selectedSection || '';
        },

        // ── events ────────────────────────────────────────────────────────────
        onSectionChange() {
            // When user picks a preset section, copy its name to customSection too
            if (this.selectedSection && this.selectedSection !== '__custom__') {
                this.customSection = this.selectedSection;
                const sec = this.readySections.find(s => s.name === this.selectedSection);
                this.sectionNameEn = sec?.name_en || '';
            } else if (this.selectedSection === '__custom__') {
                this.sectionNameEn = '';
            }
            this._applySection(this.resolvedSectionName);
        },

        onCustomSectionInput() {
            if (!this.selectedSection) this.selectedSection = '__custom__';
            this._applySection(this.customSection.trim());
        },

        onCategoryChange() {
            this._loadNotesForCategory(this.selectedCategory);
        },

        // ── internals ─────────────────────────────────────────────────────────
        _applySection(name) {
            if (!name) return;
            // find a ready section matching this name
            const sec = this.readySections.find(s => s.name === name);
            if (sec && sec.note_category_id) {
                this.selectedCategory = String(sec.note_category_id);
                this._loadNotesForCategory(this.selectedCategory);
                return;
            }
            // no ready section — keep current category (user can pick manually)
        },

        _loadNotesForCategory(catId) {
            this.categoryNotes = [];
            this.categoryRecs = [];
            this.selectedNotes = [];
            this.selectedRecsArr = [];
            if (!catId) return;
            const cat = _catById(this.allCategories, catId);
            if (!cat) return;
            this.categoryNotes = cat.ready_notes || [];
            this.categoryRecs  = cat.recommendation_templates || [];
        },

        // ── note checkbox helpers ─────────────────────────────────────────────
        _noteText(note) {
            const loc = this.resolvedSectionName || '(الموقع)';
            return String(note.text).replace(/\(الموقع\)/g, loc);
        },

        _noteTextEn(note) {
            const raw = (note.text_en && String(note.text_en).trim()) ? note.text_en : note.text;
            const loc = this.sectionNameEn || this.resolvedSectionName || '(location)';
            return String(raw).replace(/\(الموقع\)/g, loc).replace(/\(location\)/gi, loc);
        },

        _recTextEn(rec) {
            return (rec.text_en && String(rec.text_en).trim()) ? rec.text_en : rec.text;
        },

        isNoteSelected(note) { return this.selectedNotes.some(n => n.id === note.id); },
        isRecSelected(rec)   { return this.selectedRecsArr.some(r => r.id === rec.id); },

        toggleNote(note) {
            const i = this.selectedNotes.findIndex(n => n.id === note.id);
            if (i >= 0) {
                this.selectedNotes.splice(i, 1);
                const t = this._noteText(note);
                const j = this.notesList.indexOf(t);
                if (j >= 0) this.notesList.splice(j, 1);
                const te = this._noteTextEn(note);
                const je = this.notesListEn.indexOf(te);
                if (je >= 0) this.notesListEn.splice(je, 1);
            } else {
                this.selectedNotes.push(note);
                const t = this._noteText(note);
                if (!this.notesList.includes(t)) this.notesList.push(t);
                const te = this._noteTextEn(note);
                if (te && !this.notesListEn.includes(te)) this.notesListEn.push(te);
            }
            this._syncText();
        },

        toggleRec(rec) {
            const i = this.selectedRecsArr.findIndex(r => r.id === rec.id);
            if (i >= 0) {
                this.selectedRecsArr.splice(i, 1);
                const j = this.recommendationsList.indexOf(rec.text);
                if (j >= 0) this.recommendationsList.splice(j, 1);
                const te = this._recTextEn(rec);
                const je = this.recommendationsListEn.indexOf(te);
                if (je >= 0) this.recommendationsListEn.splice(je, 1);
            } else {
                this.selectedRecsArr.push(rec);
                if (!this.recommendationsList.includes(rec.text)) this.recommendationsList.push(rec.text);
                const te = this._recTextEn(rec);
                if (te && !this.recommendationsListEn.includes(te)) this.recommendationsListEn.push(te);
            }
            this._syncText();
        },

        _syncText() {
            this.notesText          = this.notesList.join('\n');
            this.recommendationsText = this.recommendationsList.join('\n');
            this.notesTextEn        = this.notesListEn.join('\n');
            this.recommendationsTextEn = this.recommendationsListEn.join('\n');
        },

        syncListsFromText() {
            this.notesList          = (this.notesText || '').split('\n').map(s => s.trim()).filter(Boolean);
            this.recommendationsList = (this.recommendationsText || '').split('\n').map(s => s.trim()).filter(Boolean);
            this.notesListEn        = (this.notesTextEn || '').split('\n').map(s => s.trim()).filter(Boolean);
            this.recommendationsListEn = (this.recommendationsTextEn || '').split('\n').map(s => s.trim()).filter(Boolean);
        },
    }));

    // ── areaCardEditor ────────────────────────────────────────────────────────

    Alpine.data('areaCardEditor', (initialNotes = [], initialRecs = [], initialAreaName = '', initialAreaNameEn = '', initialNotesEn = [], initialRecsEn = []) => ({
        editing: false,
        areaNameField: initialAreaName || '',
        areaNameEnField: initialAreaNameEn || '',
        notesList:            Array.isArray(initialNotes) ? [...initialNotes] : [],
        recommendationsList:  Array.isArray(initialRecs)  ? [...initialRecs]  : [],
        notesText:            Array.isArray(initialNotes) ? initialNotes.join('\n') : '',
        recommendationsText:  Array.isArray(initialRecs)  ? initialRecs.join('\n')  : '',
        notesTextEn:          Array.isArray(initialNotesEn) ? initialNotesEn.join('\n') : '',
        recsTextEn:           Array.isArray(initialRecsEn) ? initialRecsEn.join('\n') : '',

        // category picker state
        allCategories: [],
        selectedCategory: '',
        categoryNotes: [],
        categoryRecs: [],
        selectedNotes: [],
        selectedRecsArr: [],

        init() {
            this.allCategories = _loadCatsFromDom();
        },

        startEditing() {
            this.editing = true;
            this.notesText          = this.notesList.join('\n');
            this.recommendationsText = this.recommendationsList.join('\n');
            // pre-select category based on area name
            this.$nextTick(() => this._syncCheckboxes());
        },

        onCategoryChange() {
            this.categoryNotes = [];
            this.categoryRecs  = [];
            this.selectedNotes = [];
            this.selectedRecsArr = [];
            if (!this.selectedCategory) return;
            const cat = _catById(this.allCategories, this.selectedCategory);
            if (cat) {
                this.categoryNotes = cat.ready_notes || [];
                this.categoryRecs  = cat.recommendation_templates || [];
            }
        },

        _syncCheckboxes() {
            if (!this.selectedCategory) return;
            const cat = _catById(this.allCategories, this.selectedCategory);
            if (!cat) return;
            this.categoryNotes = cat.ready_notes || [];
            this.categoryRecs  = cat.recommendation_templates || [];

            this.selectedNotes = [];
            this.selectedRecsArr = [];
            const loc = this.areaNameField || '(الموقع)';
            for (const note of this.categoryNotes) {
                const text = String(note.text).replace(/\(الموقع\)/g, loc);
                if (this.notesList.includes(text)) this.selectedNotes.push(note);
            }
            for (const rec of this.categoryRecs) {
                if (this.recommendationsList.includes(rec.text)) this.selectedRecsArr.push(rec);
            }
        },

        _noteText(note) {
            const loc = this.areaNameField || '(الموقع)';
            return String(note.text).replace(/\(الموقع\)/g, loc);
        },

        _noteTextEn(note) {
            const raw = (note.text_en && String(note.text_en).trim()) ? note.text_en : note.text;
            const loc = this.areaNameEnField || this.areaNameField || '(location)';
            return String(raw).replace(/\(الموقع\)/g, loc).replace(/\(location\)/gi, loc);
        },

        _recTextEn(rec) {
            return (rec.text_en && String(rec.text_en).trim()) ? rec.text_en : rec.text;
        },

        isNoteSelected(note) { return this.selectedNotes.some(n => n.id === note.id); },
        isRecSelected(rec)   { return this.selectedRecsArr.some(r => r.id === rec.id); },

        toggleNote(note) {
            const i = this.selectedNotes.findIndex(n => n.id === note.id);
            if (i >= 0) {
                this.selectedNotes.splice(i, 1);
                const j = this.notesList.indexOf(this._noteText(note));
                if (j >= 0) this.notesList.splice(j, 1);
                const notesEn = (this.notesTextEn || '').split('\n').map(s => s.trim()).filter(Boolean);
                const te = this._noteTextEn(note);
                const je = notesEn.indexOf(te);
                if (je >= 0) notesEn.splice(je, 1);
                this.notesTextEn = notesEn.join('\n');
            } else {
                this.selectedNotes.push(note);
                const t = this._noteText(note);
                if (!this.notesList.includes(t)) this.notesList.push(t);
                const te = this._noteTextEn(note);
                const notesEn = (this.notesTextEn || '').split('\n').map(s => s.trim()).filter(Boolean);
                if (te && !notesEn.includes(te)) notesEn.push(te);
                this.notesTextEn = notesEn.join('\n');
            }
            this._syncText();
        },

        toggleRec(rec) {
            const i = this.selectedRecsArr.findIndex(r => r.id === rec.id);
            if (i >= 0) {
                this.selectedRecsArr.splice(i, 1);
                const j = this.recommendationsList.indexOf(rec.text);
                if (j >= 0) this.recommendationsList.splice(j, 1);
                const recsEn = (this.recsTextEn || '').split('\n').map(s => s.trim()).filter(Boolean);
                const te = this._recTextEn(rec);
                const je = recsEn.indexOf(te);
                if (je >= 0) recsEn.splice(je, 1);
                this.recsTextEn = recsEn.join('\n');
            } else {
                this.selectedRecsArr.push(rec);
                if (!this.recommendationsList.includes(rec.text)) this.recommendationsList.push(rec.text);
                const te = this._recTextEn(rec);
                const recsEn = (this.recsTextEn || '').split('\n').map(s => s.trim()).filter(Boolean);
                if (te && !recsEn.includes(te)) recsEn.push(te);
                this.recsTextEn = recsEn.join('\n');
            }
            this._syncText();
        },

        _syncText() {
            this.notesText          = this.notesList.join('\n');
            this.recommendationsText = this.recommendationsList.join('\n');
        },

        syncListsFromText() {
            this.notesList          = (this.notesText || '').split('\n').map(s => s.trim()).filter(Boolean);
            this.recommendationsList = (this.recommendationsText || '').split('\n').map(s => s.trim()).filter(Boolean);
        },
    }));
}
