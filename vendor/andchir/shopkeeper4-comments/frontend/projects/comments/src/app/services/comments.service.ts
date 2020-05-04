import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';

import {DataService} from '@app/services/data-service.abstract';
import {Comment} from '../models/comment.model';

@Injectable({
    providedIn: 'root'
})
export class CommentsService extends DataService<Comment> {

    constructor(http: HttpClient) {
        super(http);
        this.setRequestUrl('/admin/comments');
    }

}
