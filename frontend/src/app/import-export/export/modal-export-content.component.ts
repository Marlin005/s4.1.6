import {Component, ElementRef, Input} from '@angular/core';
import {FormBuilder, Validators} from '@angular/forms';
import {HttpRequest, HttpResponse, HttpHeaderResponse, HttpEventType, HttpEvent} from '@angular/common/http';

import {Observable} from 'rxjs';
import {map} from 'rxjs/operators';
import {cloneDeep, findIndex} from 'lodash';
import {NgbActiveModal, NgbModal, NgbTooltipConfig} from '@ng-bootstrap/ng-bootstrap';
import {TranslateService} from '@ngx-translate/core';

import {SettingsService} from '@app/settings/settings.service';
import {SystemNameService} from '@app/services/system-name.service';
import {ModalContentAbstractComponent} from '@app/modal.abstract';
import {ExportConfiguration} from '../models/export-configuration.model';
import {ExportService} from '../services/export-service';
import {AppSettings} from '@app/services/app-settings.service';
import {FileData} from '@app/catalog/models/file-data.model';
import {FileModel} from '@app/models/file.model';
import {FormFieldInterface} from '@app/models/form-field.interface';
import {SheetProperties} from '../models/sheet-properties.interface';
import {ContentType} from '@app/catalog/models/content_type.model';
import {ContentField} from '@app/catalog/models/content_field.model';
import {FieldOption} from '../models/import-configuration.model';
import {ContentTypesService} from '@app/catalog/services/content_types.service';

@Component({
    selector: 'app-modal-export',
    templateUrl: './templates/modal-export.html',
    providers: [ExportService]
})
export class ModalExportContentComponent extends ModalContentAbstractComponent<ExportConfiguration> {

    @Input() model = new ExportConfiguration(0, '');
    modalTitle = 'Export';
    loadingConfiguration = false;
    isInitialised = false;
    sheetHeaders: string[] = [];
    contentTypes: ContentType[] = [];
    contentType: ContentType;
    percent = 0;
    showProgressBar = false;
    baseUrl = '';

    public contentTypes$: Observable<ContentType[]>;

    formFields: FormFieldInterface = {
        title: {
            fieldLabel: 'TITLE',
            value: '',
            validators: [Validators.required],
            messages: {}
        },
        parentId: {
            fieldLabel: 'PARENT_FOLDER',
            value: '',
            dataKey: 'options',
            validators: [],
            messages: {}
        },
        type: {
            fieldLabel: 'FORMAT',
            value: 'xls',
            validators: [],
            messages: {}
        },
        contentType: {
            fieldLabel: 'CONTENT_TYPE',
            value: '',
            dataKey: 'options',
            validators: [],
            messages: {}
        }
    };

    constructor(
        public fb: FormBuilder,
        public dataService: ExportService,
        public systemNameService: SystemNameService,
        public activeModal: NgbActiveModal,
        public tooltipConfig: NgbTooltipConfig,
        public translateService: TranslateService,
        public elRef: ElementRef,
        private modalService: NgbModal,
        private settingsService: SettingsService,
        private contentTypeService: ContentTypesService,
        private appSettings: AppSettings
    ) {
        super(fb, dataService, systemNameService, activeModal, tooltipConfig, translateService, elRef);
    }

    onBeforeInit(): void {
        if (this.model && !this.model.options) {
            this.model.options = {};
        }
        if (this.model && !this.model.fieldsOptions) {
            this.model.fieldsOptions = [];
        }
        this.baseUrl = this.appSettings.settings.webApiUrl;
    }

    onAfterInit(): void {
        this.getContentTypesList();
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
        this.loading = true;
        this.contentTypeService.getItemByName(contentTypeName)
            .subscribe((contentType: ContentType) => {
                this.contentType = contentType;
                // this.updateFieldOptions();
                this.loading = false;
            }, (err) => {
                if (err['error']) {
                    this.errorMessage = err['error'];
                }
                this.contentType = null;
                this.loading = false;
            });
    }

    updateFieldOptions(): void {
        if (!this.contentType) {
            return;
        }
        const oldFieldsOptions = cloneDeep(this.model.fieldsOptions);
        this.model.fieldsOptions = [];
        this.contentType.fields.forEach((field) => {
            const index = findIndex<FieldOption>(oldFieldsOptions, {sourceName: field.name});
            const fOpt = index > -1 ? oldFieldsOptions[index] : {};
            this.model.fieldsOptions.push({
                sourceTitle: fOpt.sourceTitle || field.title,
                sourceName: fOpt.sourceName || field.name,
                targetName: fOpt.targetName || '',
                targetTitle: fOpt.targetTitle || '',
                targetAction: fOpt.targetAction || ''
            });
        });
    }

    getFieldsColumnClass(fieldOption: FieldOption): string {
        let conditions = [['field'], []],
            classNames = ['col-6 pr-2', 'col-4 pr-2'];
        let className = 'col-12';
        conditions.forEach((condition, index) => {
            if (condition.indexOf(fieldOption.targetAction) > -1) {
                className = classNames[index];
            }
        });
        return className;
    }

    onChangeFieldAction(fieldOption: FieldOption, index: number, action: string): void {
        fieldOption.targetName = '';
        fieldOption.targetTitle = '';
        fieldOption.separator = '';
        fieldOption.options = [];
        switch (action) {
            case 'delete':
                this.model.fieldsOptions.splice(index, 1);
                break;
            case 'category':
                fieldOption.targetTitle = 'Категория';
                break;
            case 'categories_splitted':
                fieldOption.targetTitle = 'Категории';
                break;
        }
    }

    onChangeFieldName(fieldOption: FieldOption, fieldName: string): void {
        if (fieldName === '_id') {
            fieldOption.targetTitle = 'ID';
        } else {
            const fieldIndex = findIndex<ContentField>(this.contentType.fields, {name: fieldName});
            fieldOption.targetTitle = this.contentType.fields[fieldIndex].title;
        }
    }

    fieldOptionsAdd(event: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        this.model.fieldsOptions.push({
            targetAction: 'category',
            targetTitle: 'Категория'
        } as FieldOption);
    }

    startExport(event?: MouseEvent): void {
        if (event) {
            event.preventDefault();
        }
        if (this.loading) {
            return;
        }
        this.errorMessage = '';
        this.loading = true;
        this.percent = 0;

        let lastChar = 0, message = '', data = {};

        this.dataService.exportDataProgress(this.model)
            .subscribe({
                next: (event) => {
                    if (event instanceof HttpResponse) {
                        this.updatePercent(100);
                    } else if (event instanceof HttpHeaderResponse) {
                        // console.log('HEADER RESPONSE', event);
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
                        }
                        if (event.type === HttpEventType.Response) {
                            // console.log('BODY', event.body);
                        }
                    }
                },
                error: (err) => {
                    if (err.error && err.error.indexOf('{') > -1) {
                        err.error = JSON.parse(err.error);
                        err.error = err.error['error'] || this.getLangString('ERROR');
                    }
                    if (err.error) {
                        this.errorMessage = err.error;
                    }
                    this.loading = false;
                    this.showProgressBar = false;
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
                this.showProgressBar = false;
                this.loading = false;
                this.getModelData();
            }, 1000);
        }
    }
}
