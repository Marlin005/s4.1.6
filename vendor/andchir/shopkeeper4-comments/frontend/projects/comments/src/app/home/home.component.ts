import {Component, OnInit} from '@angular/core';

import {NgbActiveModal, NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {TranslateService} from '@ngx-translate/core';
import {MessageService} from 'primeng/components/common/messageservice';

import {PageTableAbstractComponent} from '@app/page-table.abstract';
import {QueryOptions} from '@app/models/query-options';
import {CommentsService} from '../services/comments.service';
import {ModalCommentComponent} from './modal-comment.component';

@Component({
    selector: 'app-home',
    templateUrl: './home.component.html',
    styleUrls: ['./home.component.css'],
    providers: [MessageService, CommentsService]
})
export class HomeComponent extends PageTableAbstractComponent<Comment> {

    static title = 'COMMENTS';
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
            name: 'vote',
            sortName: 'vote',
            title: 'VOTE',
            outputType: 'text',
            outputProperties: {}
        },
        {
            name: 'status',
            sortName: 'status',
            title: 'STATUS',
            outputType: 'text',
            outputProperties: {}
        },
        {
            name: 'createdTime',
            sortName: 'createdTime',
            title: 'CREATED_TIME',
            outputType: 'date',
            outputProperties: {
                format: 'dd/MM/y H:mm:s'
            }
        },
        {
            name: 'publishedTime',
            sortName: 'publishedTime',
            title: 'PUBLISHED_TIME',
            outputType: 'date',
            outputProperties: {
                format: 'dd/MM/y H:mm:s'
            }
        },
        {
            name: 'isActive',
            sortName: 'status',
            title: 'STATUS',
            outputType: 'boolean',
            outputProperties: {}
        }
    ];

    constructor(
        public dataService: CommentsService,
        public activeModal: NgbActiveModal,
        public modalService: NgbModal,
        public translateService: TranslateService
    ) {
        super(dataService, activeModal, modalService, translateService);
    }

    getModalContent() {
        return ModalCommentComponent;
    }

    getModalElementId(itemId?: number): string {
        return ['modal', 'comment', itemId || 0].join('-');
    }

    setModalInputs(itemId?: number, isItemCopy: boolean = false, modalId = ''): void {
        super.setModalInputs(itemId, isItemCopy, modalId);

        const isEditMode = typeof itemId !== 'undefined' && !isItemCopy;
        this.modalRef.componentInstance.modalTitle = isEditMode
            ? `${this.getLangString('EDIT_COMMENT')} #${itemId}`
            : this.getLangString('ADD_COMMENT');
    }
}
