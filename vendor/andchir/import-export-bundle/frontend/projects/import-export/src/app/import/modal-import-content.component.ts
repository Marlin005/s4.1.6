import {Component, ElementRef, Input} from '@angular/core';
import {FormBuilder, Validators} from '@angular/forms';
import {HttpRequest, HttpResponse, HttpHeaderResponse, HttpEventType, HttpEvent} from '@angular/common/http';

import {Observable} from 'rxjs';
import {map} from 'rxjs/operators';
import {NgbActiveModal, NgbModal, NgbTooltipConfig, NgbModalRef} from '@ng-bootstrap/ng-bootstrap';
import {MessageService} from 'primeng/api';
import {TranslateService} from '@ngx-translate/core';
import {cloneDeep, findIndex, range} from 'lodash';

import {SettingsService} from '@app/settings/settings.service';
import {SystemNameService} from '@app/services/system-name.service';
import {ModalContentAbstractComponent} from '@app/modal.abstract';
import {FieldOption, ImportConfiguration, ImportTestData} from '../models/import-configuration.model';
import {ImportService} from '../services/import-service';
import {AppSettings} from '@app/services/app-settings.service';
import {FileData} from '@app/catalog/models/file-data.model';
import {FileModel} from '@app/models/file.model';
import {FormFieldInterface} from '@app/models/form-field.interface';
import {SheetProperties} from '../models/sheet-properties.interface';
import {ContentType} from '@app/catalog/models/content_type.model';
import {ContentTypesService} from '@app/catalog/services/content_types.service';
import {ConfirmModalContentComponent} from '@app/components/modal-confirm-text.component';

@Component({
    selector: 'app-modal-import',
    templateUrl: './templates/modal-import.html',
    providers: [ImportService, MessageService]
})
export class ModalImportContentComponent extends ModalContentAbstractComponent<ImportConfiguration> {

    @Input() model = new ImportConfiguration(0, '');
    modalTitle = 'Import';
    loadingConfiguration = false;
    isInitialised = false;
    sheetHeaders: string[] = [];
    contentTypes: ContentType[] = [];
    contentType: ContentType;
    testData: ImportTestData;
    percent = 0;
    showProgressBar = false;
    modalRef: NgbModalRef;
    steps: number[] = [1];

    public contentTypes$: Observable<ContentType[]>;

    formFields: FormFieldInterface = {
        title: {
            fieldLabel: 'TITLE',
            value: '',
            validators: [Validators.required],
            messages: {}
        },
        fileData: {
            fieldLabel: 'FILE',
            value: '',
            validators: [Validators.required],
            messages: {}
        },
        rowNumberHeaders: {
            fieldLabel: 'ROW_NUMBER_HEADERS',
            value: 1,
            dataKey: 'options',
            validators: [],
            messages: {}
        },
        rowNumberFirst: {
            fieldLabel: 'ROW_NUMBER_FIRST',
            value: 2,
            dataKey: 'options',
            validators: [],
            messages: {}
        },
        rowNumberLast: {
            fieldLabel: 'ROW_NUMBER_LAST',
            value: 0,
            dataKey: 'options',
            validators: [],
            messages: {}
        },
        categoriesSeparator: {
            fieldLabel: 'SEPARATOR_CATEGORIES',
            value: '',
            dataKey: 'options',
            validators: [],
            messages: {}
        }
    };
    files: {[key: string]: File} = {};
    timerConfiguration: any;
    timerPercent: any;

    constructor(
        public fb: FormBuilder,
        public dataService: ImportService,
        public systemNameService: SystemNameService,
        public activeModal: NgbActiveModal,
        public tooltipConfig: NgbTooltipConfig,
        public translateService: TranslateService,
        public elRef: ElementRef,
        private modalService: NgbModal,
        private settingsService: SettingsService,
        private contentTypeService: ContentTypesService,
        private appSettings: AppSettings,
        private messageService: MessageService
    ) {
        super(fb, dataService, systemNameService, activeModal, tooltipConfig, translateService, elRef);
    }

    onBeforeInit(): void {
        if (this.model && !this.model.options) {
            this.model.options = {};
        }
    }

    onAfterInit(): void {
        if (this.itemId) {
            this.getContentTypesList();
        }
    }

    onAfterGetData(): void {
        setTimeout(() => {
            this.isInitialised = true;
        }, 1);
        if (this.model.options.contentType) {
            this.getContentType();
        }
        if (!this.model.fieldsOptions || this.model.fieldsOptions.length === 0) {
            this.getSheetProperties();
        }
        this.updateStepsArr();
    }

    getFileName(fileData: FileData|string): string {
        if (typeof fileData === 'string') {
            return fileData;
        }
        return FileData.getFileName(fileData);
    }

    onOptionChanged(): void {
        if (!this.isInitialised) {
            return;
        }
        clearTimeout(this.timerConfiguration);
        this.timerConfiguration = setTimeout(this.getSheetProperties.bind(this), 700);
    }

    getContentTypesList(): void {
        this.contentTypes$ = this.contentTypeService.getListPage()
            .pipe(map(({items}) => items));
    }

    getContentType(contentTypeName?: string): void {
        if (!contentTypeName) {
            contentTypeName = this.model.options.contentType as string;
        }
        if (!contentTypeName) {
            this.contentType = null;
            return;
        }
        this.loadingConfiguration = true;
        this.contentTypeService.getItemByName(contentTypeName)
            .subscribe({
                next: (contentType: ContentType) => {
                    this.contentType = contentType;
                    this.loadingConfiguration = false;
                },
                error: (err) => {
                    if (err.error) {
                        this.errorMessage = err.error;
                    }
                    this.contentType = null;
                    this.loadingConfiguration = false;
                }
            });
    }

    onChangeFieldAction(fieldOption: FieldOption, action: string, fieldOptionParent?: FieldOption): void {
        fieldOption.targetName = '';
        fieldOption.targetTitle = '';
        fieldOption.separator = '';
        fieldOption.options = [];
        if (action === 'delete') {
            if (fieldOptionParent) {
                const index = findIndex(fieldOptionParent.options, {targetAction: 'delete'});
                if (index > -1) {
                    fieldOptionParent.options.splice(index, 1);
                }
            }
        }
    }

    getSheetProperties(event?: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        if (!this.model.options.sheetName) {
            this.sheetHeaders = [];
            return;
        }
        this.errorMessage = '';
        this.loadingConfiguration = true;
        this.dataService.getSheetProperties(this.model)
            .subscribe({
                next: (properties: SheetProperties) => {
                    this.model.fieldsOptions = properties.fieldsOptions;
                    this.model.options.rowNumberLast = properties.rowsQuantity;
                    this.loadingConfiguration = false;
                },
                error: (err) => {
                    this.errorMessage = err.error || this.getLangString('ERROR');
                    this.loadingConfiguration = false;
                }
            });
    }

    getTestData(event?: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        if (this.loading || this.loadingConfiguration) {
            return;
        }
        this.errorMessage = '';
        this.loadingConfiguration = true;
        this.dataService.testData(this.model)
            .subscribe({
                next: (testData: ImportTestData) => {
                    if (testData) {
                        this.testData = testData;
                    } else {
                        this.errorMessage = this.getLangString('ERROR');
                    }
                    this.loadingConfiguration = false;
                },
                error: (err) => {
                    this.errorMessage = err.error || this.getLangString('ERROR');
                    this.loadingConfiguration = false;
                }
            });
    }

    updateData(event?: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        if (this.testData) {
            this.getTestData();
        } else {
            this.getSheetProperties();
        }
    }

    fieldAddOptions(fieldOption: FieldOption, event?: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        fieldOption.options.push({targetAction: ''} as FieldOption);
    }

    getIsFieldsCount(fieldOption: FieldOption, fieldsCount: number): boolean {
        switch (fieldsCount) {
            case 1:
                return !fieldOption.targetAction || ['category'].indexOf(fieldOption.targetAction) > -1;
                break;
            case 2:
                return ['field', 'split'].indexOf(fieldOption.targetAction) > -1;
                break;
            case 3:
                return ['new'].indexOf(fieldOption.targetAction) > -1;
                break;
        }
        return true;
    }

    getIsParametersField(fieldName: string): boolean {
        if (!this.contentType) {
            return false;
        }
        const contentTypeFields = this.contentType.fields;
        const parametersFields = contentTypeFields.filter((field) => {
            return field.inputType === 'parameters';
        });
        const parametersFieldNames = parametersFields.map((field) => {
            return field.name;
        });
        return parametersFieldNames.indexOf(fieldName) > -1;
    }

    getFieldsColumnClass(fieldOption: FieldOption, fieldType: string): string {
        let conditions = [['split', 'field', 'new'], []],
            classNames = ['col-6 pr-2', 'col-4 pr-2'];
        const isParameter = this.getIsParametersField(fieldOption.targetName);
        if (fieldType == 'field_sub') {
            conditions = [['split'], ['new']];
            if (isParameter) {
                conditions[1].push('field');
            } else {
                conditions[0].push('field');
            }
            classNames = ['col-6 pr-2', 'col-4 pr-2'];
        }
        let className = 'col-12';
        conditions.forEach((condition, index) => {
            if (condition.indexOf(fieldOption.targetAction) > -1) {
                className = classNames[index];
            }
        });
        return className;
    }

    onStepNumberChange(): void {
        this.updateStepsArr();
    }

    onImportCompleted(): void {
        this.showProgressBar = false;
        this.loading = false;
        this.activeModal.close({reason: 'import_completed'});
    }

    updateStepsArr(): void {
        this.steps = range(1, Math.max(1, parseInt(String(this.model.options.stepsNumber)) + 1));
        this.model.options.step = 1;
    }

    startImport(event?: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        if (this.loading || this.loadingConfiguration) {
            return;
        }
        this.errorMessage = '';
        this.testData = null;
        this.loading = true;
        this.percent = 0;

        let lastChar = 0, message = '', data = {};

        this.dataService.importDataProgress(this.model)
            .subscribe({
                next: (event) => {
                    if (event instanceof HttpResponse) {
                        this.updatePercent(100);
                    } else if (event instanceof HttpHeaderResponse) {
                        // console.log('HEADER RESPONSE', event);
                        if (event.status !== 200 && event.status !== 400) {
                            this.onImportCompleted();
                        }
                    } else {
                        if (event.type === HttpEventType.DownloadProgress) {
                            message = (event.partialText.substr(lastChar)).trim();
                            const tmpArr = message.split("\n");
                            message = tmpArr[tmpArr.length - 1];
                            lastChar = event.partialText.length;
                            if (message.substr(0, 1) === '{'
                                && message.substr(message.length - 1, 1) === '}') {
                                data = JSON.parse(message);
                                const maxValue = data['rowNumberLast'] - data['rowNumberFirst'],
                                    currValue = data['currentIndex'] - data['rowNumberFirst'];

                                this.updatePercent(currValue / maxValue * 100);
                            }
                        } else if (event.type === HttpEventType.Response) {
                            // console.log('BODY', event.body);
                        }
                    }
                },
                error: (err) => {
                    if (err.error && err.error.indexOf('{') > -1) {
                        err.error = JSON.parse(err.error);
                        err.error = err.error['error'] || this.getLangString('ERROR');
                    }
                    this.errorMessage = err.error || this.getLangString('ERROR');
                    this.showProgressBar = false;
                    this.loading = false;
                }
            });
    }

    updatePercent(percent: number): void {
        this.percent = Math.min(100, Math.floor(percent));
        if (this.percent > 0 && !this.showProgressBar) {
            this.showProgressBar = true;
        }
        if (this.percent === 100) {
            setTimeout(() => {
                this.onImportCompleted();
            }, 1000);
        }
    }

    confirmAction(message: string) {
        this.modalRef = this.modalService.open(ConfirmModalContentComponent);
        this.modalRef.componentInstance.modalTitle = this.getLangString('CONFIRM');
        this.modalRef.componentInstance.modalContent = message;
        return this.modalRef.result;
    }

    clearTestData(event?: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        this.testData = null;
    }

    saveRequest() {
        if (this.isEditMode) {
            return this.dataService.update(this.getFormData());
        } else {
            return this.dataService.create(this.getFormData());
        }
    }
}
