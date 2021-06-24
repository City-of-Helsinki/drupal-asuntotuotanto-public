(($, Drupal) => {
  Drupal.behaviors.applicationFormActions = {
    attach: function attach() {
      const applicationFormApartmentListElement = document.getElementById(
        "application_form_apartments_list"
      );

      const getApplicationFormApartmentListElementCount = () =>
        applicationFormApartmentListElement.getElementsByClassName(
          "application-form__apartments-item"
        ).length;

      const getLastOriginalApartmentSelectElement = () => {
        const originalApartmentSelectElement = document.querySelector(
          '[data-drupal-selector="edit-apartment-0-id"]'
        );

        const originalApartmentSelectElementWrapper =
          originalApartmentSelectElement.parentElement.parentElement
            .parentElement.parentElement.parentElement;

        const selectCount =
          originalApartmentSelectElementWrapper.children.length;

        const lastSelectParent =
          originalApartmentSelectElementWrapper.children[selectCount - 1];

        return lastSelectParent.getElementsByTagName("select")[0];
      };

      const createParagraphElementWithVisuallyHiddenText = (
        classes,
        hiddenTextString,
        visibleString
      ) => {
        const p = document.createElement("p");
        p.classList.add(...classes);

        const span1 = document.createElement("span");
        const span1Content = document.createTextNode(
          Drupal.t(hiddenTextString)
        );
        span1.classList.add("visually-hidden");
        span1.appendChild(span1Content);

        const span2 = document.createElement("span");
        const span2Content = document.createTextNode(visibleString);
        span2.appendChild(span2Content);

        p.append(span1, span2);

        return p;
      };

      const createButtonElement = (classes, content, disabled = false) => {
        const button = document.createElement("button");
        button.classList.add(...classes);
        const span = document.createElement("span");
        const text = document.createTextNode(Drupal.t(content));

        span.append(text);
        button.append(span);

        button.setAttribute("type", "button");

        if (disabled) button.disabled = true;

        return button;
      };

      const createListItemElementWithText = (description, value) => {
        const liElement = document.createElement("li");
        const span1 = document.createElement("span");
        const text1 = document.createTextNode(Drupal.t(description));
        span1.appendChild(text1);

        const span2 = document.createElement("span");
        const text2 = document.createTextNode(Drupal.t(value));
        span2.appendChild(text2);

        liElement.append(span1, span2);

        return liElement;
      };

      const setFocusToLastSelectElement = () => {
        const allCustomSelectElements = document.querySelectorAll(
          '[data-drupal-selector="custom_apartment_select"]'
        );

        const customSelectCount = allCustomSelectElements.length;

        const lastCustomSelect = allCustomSelectElements[customSelectCount - 1];

        lastCustomSelect.focus();
      };

      const createCustomSelectElement = () => {
        const apartmentListElementWrapper = document.createElement("div");
        apartmentListElementWrapper.classList.add(
          "application-form-apartment__apartment-add-actions-wrapper"
        );

        const selectCount = getApplicationFormApartmentListElementCount() - 1;
        const selectElementId = `apartment_list_select_${selectCount}`;

        const apartmentListElement = document.createElement("div");
        apartmentListElement.classList.add("hds-select-element");

        const apartmentSelectElementLabel = document.createElement("label");
        const apartmentSelectElementLabelText = document.createTextNode(
          Drupal.t("Apartment")
        );
        apartmentSelectElementLabel.appendChild(
          apartmentSelectElementLabelText
        );

        apartmentSelectElementLabel.setAttribute("for", selectElementId);

        const apartmentSelectElementWrapper = document.createElement("div");
        apartmentSelectElementWrapper.classList.add(
          "hds-select-element__select-wrapper"
        );

        const apartmentSelectElement = getLastOriginalApartmentSelectElement().cloneNode(
          true
        );

        apartmentSelectElement.classList.add("hds-select-element__select");
        apartmentSelectElement.setAttribute("id", selectElementId);
        apartmentSelectElement.setAttribute("data-id", selectCount);
        apartmentSelectElement.setAttribute(
          "data-drupal-selector",
          "custom_apartment_select"
        );

        apartmentSelectElement.addEventListener("change", ({ target }) => {
          const originalSelectElementTarget = document.querySelector(
            `[data-drupal-selector="edit-apartment-${selectCount}-id"]`
          );

          const targetParent =
            target.parentElement.parentElement.parentElement.parentElement
              .parentElement.parentElement;
          targetParent.setAttribute("data-id", selectCount);

          originalSelectElementTarget.value = target.value;
          targetParent.classList.remove(
            "application-form__apartments-item--with-select"
          );
          // eslint-disable-next-line no-use-before-define
          targetParent.innerHTML = createApartmentListItem().innerHTML;
        });

        apartmentSelectElementWrapper.appendChild(apartmentSelectElement);

        apartmentListElement.append(
          apartmentSelectElementLabel,
          apartmentSelectElementWrapper
        );
        apartmentListElementWrapper.appendChild(apartmentListElement);

        return apartmentListElementWrapper;
      };

      const createElementWithClasses = (tag, classes = []) => {
        const element = document.createElement(tag);
        element.classList.add(...classes);

        return element;
      };

      const swapOriginalSelectWeights = (select1Id, select2Id) => {
        const select1WeigthElement = document.querySelector(
          `[name="apartment[${select1Id}][_weight]"]`
        );
        const select2WeigthElement = document.querySelector(
          `[name="apartment[${select2Id}][_weight]"]`
        );
        const select1Weigth = select1WeigthElement.value;
        select1WeigthElement.value = select2WeigthElement.value;
        select2WeigthElement.value = select1Weigth;
      };

      const handleListPositionRaiseClick = (target) => {
        const parent = target.parentElement.parentElement.parentElement;
        const sibling = parent.previousElementSibling;

        if (sibling !== null) {
          sibling.before(parent);
          swapOriginalSelectWeights(
            parent.getAttribute("data-id"),
            sibling.getAttribute("data-id")
          );
        }
      };

      const handleListPositionLowerClick = (target) => {
        const parent = target.parentElement.parentElement.parentElement;
        const sibling = parent.nextElementSibling;

        if (sibling !== null) {
          if (
            !sibling.classList.contains(
              "application-form__apartments-item--with-select"
            )
          ) {
            sibling.after(parent);
            swapOriginalSelectWeights(
              parent.getAttribute("data-id"),
              sibling.getAttribute("data-id")
            );
          }
        }
      };

      const handleListPositionClicks = ({ target }) => {
        if (
          target.getAttribute("data-list-position-action-button") === "raise"
        ) {
          handleListPositionRaiseClick(target);
        }

        if (
          target.getAttribute("data-list-position-action-button") === "lower"
        ) {
          handleListPositionLowerClick(target);
        }
      };

      const handleApartmentAddButtonClick = ({ target }) => {
        const ajaxButton = $(
          '[data-drupal-selector="edit-apartment-add-more"]'
        );

        if (
          getApplicationFormApartmentListElementCount() <= 5 &&
          getApplicationFormApartmentListElementCount() > 1
        ) {
          ajaxButton.mousedown();
        }

        const formHeader = target.parentElement;

        const parentLiElement =
          target.parentElement.parentElement.parentElement.parentElement;

        parentLiElement.addEventListener("click", handleListPositionClicks);

        formHeader.appendChild(createCustomSelectElement());
        target.remove();
        setFocusToLastSelectElement();

        if (getApplicationFormApartmentListElementCount() < 5) {
          // eslint-disable-next-line no-use-before-define
          appendListItemToApartmentList();
        }
      };

      const createApartmentListItem = (withSelectElement = false) => {
        const li = createElementWithClasses("li", [
          "application-form__apartments-item",
        ]);

        if (withSelectElement) {
          li.classList.add("application-form__apartments-item--with-select");
        }

        const article = createElementWithClasses("article", [
          "application-form-apartment",
        ]);

        const listPositionDesktop = createParagraphElementWithVisuallyHiddenText(
          ["application-form-apartment__list-position", "is-desktop"],
          "List position",
          ""
        );

        const formHeader = createElementWithClasses("div", [
          "application-form-apartment__header",
        ]);

        const listPositionMobile = createParagraphElementWithVisuallyHiddenText(
          ["application-form-apartment__list-position", "is-mobile"],
          "List position",
          ""
        );

        const apartmentNumber = createParagraphElementWithVisuallyHiddenText(
          ["application-form-apartment__apartment-number"],
          "Apartment",
          "A75"
        );

        const apartmentStructure = createParagraphElementWithVisuallyHiddenText(
          ["application-form-apartment__apartment-structure"],
          "Apartment structure",
          "4h, kt, s"
        );

        const apartmentAddButton = createButtonElement(
          ["application-form-apartment__apartment-add-button"],
          "Add an apartment to the list"
        );

        apartmentAddButton.addEventListener(
          "click",
          handleApartmentAddButtonClick
        );

        if (withSelectElement) {
          formHeader.append(listPositionMobile, apartmentAddButton);
        } else {
          formHeader.append(
            listPositionMobile,
            apartmentNumber,
            apartmentStructure
          );
        }

        const listPositionActions = document.createElement("div");
        listPositionActions.classList.add(
          "application-form-apartment__list-position-actions"
        );

        const listPositionActionsRaiseButton = createButtonElement(
          "",
          "Raise on the list",
          withSelectElement && true
        );

        listPositionActionsRaiseButton.setAttribute(
          "data-list-position-action-button",
          "raise"
        );

        const listPositionActionsLowerButton = createButtonElement(
          "",
          "Lower on the list",
          withSelectElement && true
        );

        listPositionActionsLowerButton.setAttribute(
          "data-list-position-action-button",
          "lower"
        );

        listPositionActions.append(
          listPositionActionsRaiseButton,
          listPositionActionsLowerButton
        );

        const formApartmentInformation = createElementWithClasses("ul", [
          "application-form-apartment__information",
        ]);

        const formApartmentInformationFloor = createListItemElementWithText(
          "Floor",
          "7/7"
        );

        const formApartmentInformationLivingAreaSize = createListItemElementWithText(
          "Living area size",
          "85,0 m2"
        );

        const formApartmentInformationSalesPrice = createListItemElementWithText(
          "Sales price",
          "308 128 €"
        );

        const formApartmentInformationDebtFreeSalesPrice = createListItemElementWithText(
          "Debt free sales price",
          "378 128 €"
        );

        formApartmentInformation.append(
          formApartmentInformationFloor,
          formApartmentInformationLivingAreaSize,
          formApartmentInformationSalesPrice,
          formApartmentInformationDebtFreeSalesPrice
        );

        const formActions = createElementWithClasses("div", [
          "application-form-apartment__actions",
        ]);

        const formActionsDeleteButton = createButtonElement("", "Delete");

        const formActionsLink = document.createElement("a");
        const formActionsLinkText = document.createTextNode(
          Drupal.t("Open apartment page")
        );
        formActionsLink.appendChild(formActionsLinkText);
        formActionsLink.setAttribute("href", "https://google.fi");

        formActions.append(formActionsDeleteButton, formActionsLink);

        if (withSelectElement) {
          article.append(listPositionDesktop, formHeader, listPositionActions);
        } else {
          article.append(
            listPositionDesktop,
            formHeader,
            listPositionActions,
            formApartmentInformation,
            formActions
          );
        }

        li.appendChild(article);

        return li;
      };

      const appendListItemToApartmentList = () => {
        applicationFormApartmentListElement.append(
          createApartmentListItem(true)
        );
      };

      if (getApplicationFormApartmentListElementCount() === 0) {
        appendListItemToApartmentList();
      }
    },
  };
})(jQuery, Drupal);
