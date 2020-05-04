import {Component, OnInit} from '@angular/core';

import {findIndex} from 'lodash';
import {NgbActiveModal, NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {TranslateService} from '@ngx-translate/core';
import {MessageService} from 'primeng/api';

import {PageTableAbstractComponent} from '@app/page-table.abstract';
import {ImportConfiguration} from '../models/import-configuration.model';
import {QueryOptions} from '@app/models/query-options';
import {ImportService} from '../services/import-service';
import {ModalImportContentComponent} from './modal-import-content.component';
import {SystemNameService} from '@app/services/system-name.service';
import {SettingsService} from '@app/settings/settings.service';

@Component({
    selector: 'app-import',
    templateUrl: './templates/import.component.html',
    styleUrls: ['./import.component.css'],
    providers: [ImportService, MessageService]
})
export class ImportComponent extends PageTableAbstractComponent<ImportConfiguration> {

    static title = 'IMPORT_EXPORT';
    queryOptions: QueryOptions = new QueryOptions('id', 'desc', 1, 10, 0, 0);
    tableFields = [
        {
            name: 'id',
            sortName: 'id',
            title: 'ID',
            outputType: 'text',
            outputProperties: {}
        },
        {
            name: 'title',
            sortName: 'title',
            title: 'TITLE',
            outputType: 'text',
            outputProperties: {}
        },
        {
            name: 'fileSize',
            sortName: 'fileSize',
            title: 'FILE_SIZE_MB',
            outputType: 'number',
            outputProperties: {}
        },
        {
            name: 'type',
            sortName: 'type',
            title: 'FILE_TYPE',
            outputType: 'text',
            outputProperties: {}
        },
        {
            name: 'sheetsQuantity',
            sortName: 'sheetsQuantity',
            title: 'SHEETS_QUANTITY',
            outputType: 'text',
            outputProperties: {}
        },
        {
            name: 'rowsQuantity',
            sortName: 'rowsQuantity',
            title: 'ROWS_QUANTITY',
            outputType: 'text',
            outputProperties: {}
        }
    ];

    constructor(
        public dataService: ImportService,
        public activeModal: NgbActiveModal,
        public modalService: NgbModal,
        public translateService: TranslateService,
        private messageService: MessageService
    ) {
        super(dataService, activeModal, modalService, translateService);
    }

    onModalClose(result: any): void {
        const reason = result && result['reason'] ? result['reason'] : '';
        if (reason === 'import_completed') {
            this.messageService.add({
                key: 'message',
                severity: 'success',
                summary: this.getLangString('MESSAGE'),
                detail: this.getLangString('IMPORT_COMPLETED')
            });
        }
    }

    getModalContent() {
        return ModalImportContentComponent;
    }

    appendFormData(formData: FormData): void {
        formData.append('ownerType', 'import_export');
    }

    getModalElementId(itemId?: number): string {
        return ['modal', 'export-config', itemId || 0].join('-');
    }

    setModalInputs(itemId?: number, isItemCopy: boolean = false, modalId = ''): void {
        super.setModalInputs(itemId, isItemCopy, modalId);

        const isEditMode = typeof itemId !== 'undefined' && !isItemCopy;
        this.modalRef.componentInstance.modalTitle = isEditMode
            ? `${this.getLangString('EDIT_IMPORT_CONFIGURATION')} #${itemId}`
            : this.getLangString('ADD_IMPORT_CONFIGURATION');

        const index = findIndex(this.items, {id: itemId});
        if (index > -1) {
            this.modalRef.componentInstance.model = this.items[index];
        }
    }
}
