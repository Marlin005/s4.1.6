import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ModalCommentComponent } from './modal-comment.component';

describe('ModalCommentComponent', () => {
  let component: ModalCommentComponent;
  let fixture: ComponentFixture<ModalCommentComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ModalCommentComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ModalCommentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
