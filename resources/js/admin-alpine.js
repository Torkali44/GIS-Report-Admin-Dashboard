/**
 * Admin Alpine components — must load before Alpine.start()
 */

function loadCategoriesFromDom() {
    const catEl = document.getElementById('categories-json-data');
    if (!catEl) {
        return [];
    }
    try {
        return JSON.parse(catEl.textContent.trim());
    } catch (e) {
        console.error('categories-json', e);
        return [];
    }
}

function categoryPickerMixin(getSectionName) {
    return {
        selectedCategory: '',
        allCategories: [],
        categoryNotes: [],
        categoryRecs: [],
        selectedNotes: [],
        selectedRecsArr: [],

        loadCategoriesJson() {
            this.allCategories = loadCategoriesFromDom();
        },

        loadCategoryData(clearSelections = true) {
            this.categoryNotes = [];
            this.categoryRecs = [];
            if (clearSelections) {
                this.selectedNotes = [];
                this.selectedRecsArr = [];
            }
            if (!this.selectedCategory) {
                return;
            }

            const cat = this.allCategories.find((c) => String(c.id) === String(this.selectedCategory));
            if (cat) {
                this.categoryNotes = cat.ready_notes || [];
                this.categoryRecs = cat.recommendation_templates || [];
            }
        },

        syncCheckboxesFromLists() {
            if (!this.selectedCategory) {
                return;
            }
            this.loadCategoryData(false);

            for (const note of this.categoryNotes) {
                const text = this.noteTextFromTemplate(note);
                if (this.notesList.includes(text) && !this.isNoteSelected(note)) {
                    this.selectedNotes.push(note);
                }
            }
            for (const rec of this.categoryRecs) {
                if (this.recommendationsList.includes(rec.text) && !this.isRecSelected(rec)) {
                    this.selectedRecsArr.push(rec);
                }
            }
        },

        isNoteSelected(note) {
            return this.selectedNotes.some((n) => n.id === note.id);
        },

        isRecSelected(rec) {
            return this.selectedRecsArr.some((r) => r.id === rec.id);
        },

        toggleNote(note) {
            const idx = this.selectedNotes.findIndex((n) => n.id === note.id);
            if (idx >= 0) {
                this.selectedNotes.splice(idx, 1);
                this.removeNoteText(note);
            } else {
                this.selectedNotes.push(note);
                this.addNoteText(note);
            }
        },

        toggleRec(rec) {
            const idx = this.selectedRecsArr.findIndex((r) => r.id === rec.id);
            if (idx >= 0) {
                this.selectedRecsArr.splice(idx, 1);
                this.removeRecText(rec);
            } else {
                this.selectedRecsArr.push(rec);
                this.addRecText(rec);
            }
        },

        noteTextFromTemplate(note) {
            const section = getSectionName.call(this) || '(الموقع)';
            return String(note.text).replace(/\(الموقع\)/g, section);
        },

        addNoteText(note) {
            const text = this.noteTextFromTemplate(note);
            if (!this.notesList.includes(text)) {
                this.notesList.push(text);
            }
        },

        removeNoteText(note) {
            const text = this.noteTextFromTemplate(note);
            const idx = this.notesList.indexOf(text);
            if (idx >= 0) {
                this.notesList.splice(idx, 1);
            }
        },

        addRecText(rec) {
            if (!this.recommendationsList.includes(rec.text)) {
                this.recommendationsList.push(rec.text);
            }
        },

        removeRecText(rec) {
            const idx = this.recommendationsList.indexOf(rec.text);
            if (idx >= 0) {
                this.recommendationsList.splice(idx, 1);
            }
        },
    };
}

export function registerAdminAlpine(Alpine) {
    Alpine.data('areaCardEditor', (initialNotes = [], initialRecs = [], initialAreaName = '') => ({
        editing: false,
        areaNameField: initialAreaName || '',
        notesList: Array.isArray(initialNotes) ? [...initialNotes] : [],
        recommendationsList: Array.isArray(initialRecs) ? [...initialRecs] : [],

        ...categoryPickerMixin(function sectionName() {
            return this.areaNameField || '';
        }),

        init() {
            this.loadCategoriesJson();
        },

        startEditing() {
            this.editing = true;
            this.$nextTick(() => this.syncCheckboxesFromLists());
        },
    }));

    Alpine.data('areaForm', () => ({
        selectedSection: '',
        customSection: '',
        readySections: [],
        notesList: [],
        recommendationsList: [],

        ...categoryPickerMixin(function sectionName() {
            return this.selectedSection === '__custom__' ? this.customSection : this.selectedSection;
        }),

        init() {
            this.loadCategoriesJson();
            const secEl = document.getElementById('ready-sections-json-data');
            if (secEl) {
                try {
                    this.readySections = JSON.parse(secEl.textContent.trim());
                } catch (e) {
                    console.error('ready-sections-json', e);
                }
            }
        },

        onSectionChange() {
            const section = this.readySections.find((s) => s.name === this.sectionName());
            if (section?.note_category_id) {
                this.selectedCategory = String(section.note_category_id);
                this.loadCategoryData();
            }
        },

        sectionName() {
            return this.selectedSection === '__custom__' ? this.customSection : this.selectedSection;
        },
    }));
}
