import {Component, OnInit} from '@angular/core';

import {NgbActiveModal, NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {TranslateService} from '@ngx-translate/core';
import {MessageService} from 'primeng/api';

import {PageTableAbstractComponent} from '@app/page-table.abstract';
import {QueryOptions} from '@app/models/query-options';
import {ExportConfiguration} from '../models/export-configuration.model';
import {ModalExportContentComponent} from './modal-export-content.component';
import {ExportService} from '../services/export-service';

@Component({
    selector: 'app-export',
    templateUrl: './templates/export.component.html',
    styleUrls: ['./export.component.css'],
    providers: [ExportService, MessageService]
})
export class ExportComponent extends PageTableAbstractComponent<ExportConfiguration> {

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
        }
    ];

    constructor(
        public dataService: ExportService,
        public activeModal: NgbActiveModal,
        public modalService: NgbModal,
        public translateService: TranslateService,
        private messageService: MessageService
    ) {
        super(dataService, activeModal, modalService, translateService);
    }

    getModalContent() {
        return ModalExportContentComponent;
    }

    getModalElementId(itemId?: number): string {
        return ['modal', 'import-config', itemId || 0].join('-');
    }

    setModalInputs(itemId?: number, isItemCopy: boolean = false, modalId = ''): void {
        super.setModalInputs(itemId, isItemCopy, modalId);

        const isEditMode = typeof itemId !== 'undefined' && !isItemCopy;
        this.modalRef.componentInstance.modalTitle = isEditMode
            ? `${this.getLangString('EDIT_EXPORT_CONFIGURATION')} #${itemId}`
            : this.getLangString('ADD_EXPORT_CONFIGURATION');
    }
}
